# Note de conception

## 1. Choix principaux

Au départ, `BookingService::confirm()` faisait tout : validation, calcul, remises, paiement, SQL et email. Il ne fait plus qu'orchestrer :

valider → calculer le total → payer → marquer confirmée → sauvegarder → déclencher les réactions.

Chaque besoin du backlog a maintenant un endroit unique où il change :

| Besoin qui évolue | Ce qu'on modifie |
|---|---|
| Une règle de prix | une classe `PricingRule` + son ordre dans `EventFlowFactory` |
| Un nouveau prestataire de paiement | un nouvel adapter `PaymentGateway` + une ligne dans `EventFlowFactory` |
| Une nouvelle réaction après confirmation | une nouvelle classe `BookingConfirmedListener` + une ligne dans `EventFlowFactory` |
| La supervision | `MonitoredPaymentGateway`, ou son retrait dans `EventFlowFactory` |
| La base de données | une autre implémentation de `BookingRepository` |

Autres décisions :

- **`Money` en centimes entiers** : l'ancien calcul donnait `143.82000000000002`, PayFast attend des centimes, et un montant ne peut pas être négatif (`Money cannot be negative`).
- **Enums** `CustomerType`, `PassType`, `BookingStatus` à la place des chaînes magiques (`'vip'`, `'3days'`, `'confirmed'`).
- **Validation dans `Booking::assertCanBeConfirmed()`** : la réservation connaît ses propres règles. Nous avons ajouté la garde `Booking already confirmed`, car l'audit a montré qu'une réservation pouvait être payée deux fois. Le statut ne peut plus être modifié de l'extérieur : seul `Booking::markAsConfirmed()` le change.
- **Total à 0 € après remises** : aucun prestataire n'est appelé (`PaymentReceipt::noPaymentRequired()`). Avant, ce cas finissait en erreur `Invalid amount` chez Stripe. C'est une décision métier assumée, couverte par le test `free booking is confirmed without payment`.
- **Points de fidélité** : le sujet ne fixe pas la règle. Nous avons choisi 1 point par euro entier payé (`AwardLoyaltyPoints::CENTS_PER_POINT`).
- **Échec de paiement** : les erreurs de Stripe et de PayFast deviennent une `PaymentFailedException`. Dans ce cas, rien n'est confirmé, sauvegardé ni notifié (tests `booking is not saved when payment fails` et `no listener is notified when payment fails`).

## 2. Principes SOLID mobilisés

**S — Responsabilité unique**
- Problème initial : `BookingService::confirm()` mélangeait sept responsabilités.
- Classes concernées : `Booking` (cohérence), `PriceCalculator` et ses règles (prix), adapters de paiement (prestataires), `ConsoleBookingRepository` (persistance), listeners (réactions), `MonitoredPaymentGateway` (supervision).
- Bénéfice : chaque ticket a été livré dans des classes dédiées, chacune avec son propre fichier de test.

**O — Ouvert/fermé**
- Problème initial : chaque nouveau prestataire ou réaction ajoutait un `elseif` dans `confirm()`.
- Classes concernées : `PaymentGateways`, `BookingConfirmedListener`, `PricingRule`.
- Bénéfice : le commit #105 (supervision) ne modifie pas `BookingService`, seulement de nouveaux fichiers et `EventFlowFactory`. Une 5e réaction (Slack par exemple) ou un 3e prestataire ne demanderait pas non plus de modifier `BookingService`.

**D — Inversion des dépendances**
- Problème initial : `new StripeClient()` et `new EmailService()` étaient créés dans le métier, impossible à remplacer dans un test.
- Classes concernées : `BookingService` dépend de `PriceCalculator`, `PaymentGateways`, `BookingRepository` et `BookingConfirmedListener`. Seule `EventFlowFactory` connaît les classes concrètes.
- Bénéfice : les tests utilisent des doublures (`FakePaymentGateway`, `InMemoryBookingRepository`, `RecordingListener`, `InMemoryLogger`) sans aucun effet de bord.

**L — Substitution de Liskov** (vérifiée) : `MonitoredPaymentGateway` peut remplacer n'importe quel `PaymentGateway` : même nom, même reçu, même exception relancée (test `failure is rethrown unchanged to the caller`).

## 3. Design Patterns utilisés

**Adapter — `StripePaymentGateway`, `PayFastPaymentGateway`**
- Problème : `PayFastSdk::executePayment()` prend un tableau avec un montant en centimes et renvoie un tableau `success/transaction_id`, alors que `StripeClient::charge()` prend un `float` et lève une exception. Deux API différentes, et PayFast ne doit pas être modifié.
- Solution : chaque adapter traduit vers une interface commune, `PaymentGateway::charge(Money, string): PaymentReceipt`.
- Pourquoi pas plus simple : appeler le SDK directement dans `confirm()` aurait mis les détails de PayFast (centimes, format de réponse) dans le métier, ce que le ticket #103 interdit.

**Decorator — `MonitoredPaymentGateway`**
- Problème : mesurer la durée, le montant et le résultat de chaque paiement, sans modifier `StripeClient` ni `PayFastSdk`, et sans polluer le métier.
- Solution : un `PaymentGateway` qui en enveloppe un autre et journalise avant et après l'appel.
- Pourquoi pas plus simple : mettre `hrtime()` et des logs dans `BookingService` aurait mélangé technique et métier. Les mettre dans chaque adapter aurait dupliqué le code. Le decorator s'applique à tous les prestataires, présents et futurs, et se retire en une ligne dans `EventFlowFactory`.

**Strategy — `PricingRule` (`VipTierDiscount`, `ThreeDayPassDiscount`), appliquées par `PriceCalculator`**
- Problème : la politique tarifaire change aujourd'hui, et le service commercial annonce d'autres politiques.
- Solution : une règle = une classe. `PriceCalculator` les applique dans l'ordre, et cet ordre a un sens : la remise VIP se calcule sur le total initial, puis le Pass 3 jours retire 20 € au montant final.
- Pourquoi pas plus simple : une seule classe avec deux méthodes privées aurait suffi pour ces deux règles. Nous avons gardé l'interface parce que l'évolution est annoncée dans le sujet, et parce que chaque règle se teste seule (`three day discount ignores day passes`). Nous n'avons pas ajouté de fabrique de politiques ni de fichier de configuration : ce serait spéculatif.

**Observer (simplifié) — `BookingConfirmedListener`**
- Problème : quatre réactions aujourd'hui, d'autres demain, sans réécrire la confirmation.
- Solution : `BookingService` reçoit une liste de listeners et les appelle après la sauvegarde.
- Pourquoi pas plus simple : appeler les quatre services en dur dans `confirm()` violait la contrainte du ticket #104. À l'inverse, nous n'avons pas créé de dispatcher d'événements générique : une boucle de trois lignes suffit.

**Pas de pattern** pour la validation (`Booking::assertCanBeConfirmed()`) ni pour `Money` (simple objet valeur) : le problème était l'emplacement du code et la représentation de l'argent, pas une variation de comportement.

## 4. Solutions envisagées puis écartées

- **Une fabrique avec un `match` sur le nom du moyen de paiement** : écartée au profit de `PaymentGateways`, qui range les gateways par leur `name()`. Ajouter un prestataire ne demande donc de modifier aucun `match`.
- **Superviser dans `BookingService`** : écarté, la supervision n'est pas une règle métier (ticket #105).
- **Valider la quantité dans le constructeur de `BookingItem`** : plus strict, mais l'erreur serait apparue à l'ajout du billet au lieu de la confirmation. Nous avons préféré garder le comportement caractérisé par les tests.
- **Garder des `float` en arrondissant au cas par cas** : chaque calcul aurait dû penser à arrondir. `Money` centralise la règle.
- **Un dispatcher d'événements générique** : surdimensionné pour un seul événement.

## 5. Ce que nous améliorerions avec plus de temps

- **Isoler les échecs des réactions** : si l'envoi du SMS échoue, l'exception remonte alors que le paiement est déjà fait. Il faudrait capturer et journaliser l'erreur de chaque listener, ou passer par une file de messages.
- **Cohérence paiement / sauvegarde** : si `BookingRepository::save()` échoue après le paiement, le client est débité sans réservation enregistrée. Avec une vraie base, il faudrait une transaction ou un remboursement automatique.
- **Bonus annulation / remboursement** : le `PaymentReceipt` gardé sur la réservation (prestataire + transaction) prépare cette évolution. Il faudrait ajouter une méthode `refund()` aux adapters.
- **Désactiver la supervision par configuration** plutôt qu'en modifiant `EventFlowFactory`.
- **PHPUnit**, si le projet accepte une dépendance de développement.
