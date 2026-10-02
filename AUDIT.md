Audit initial
Comportement observable
l'application décrit le type de carte utilisé pour l'achat dans ce cas un paiement Stripe de 143,82.

Une réservation 1001 est crée par l'opération SQL INSERT puis elle est enregistrée ainsi avec un statut confirmed

il y'a un e-mail de confirmation envoyé à lea example.com lui communiquant que la réservation 1001 est confirmée

Problèmes identifiés
| # | Problème | Catégorie | Impact |

| 1 | BookingService | responsabilités | si l'on modifie une de ses fonctionnalités alors il faudras aussi modifier BookingService. |
| 2 | TestRunner | Couplage | Elle n'utilise pas la bonne API |
| 3 | Characterization | testabilité | Characterization dépend du comportement de BookingService et certaines de ses dépendance  |
| 4 | PayFastSdk | Couplage | ça fait devenir bien dépendant de l'interface de PayFast 
| 5 
| 6 

Nos trois priorités
BookingService car modifier d'autres fonctionnalités nous forcerais alors à modifier BookingService aussi, en fesant une priorité
TestRunner
Characterization

Risques avant refactoring
Un client VIP bénéficie actuellement d'une remise de 10 %.
Un Pass 3 jours bénéficie actuellement d'une remise fixe de 10 €.
L'ordre d'application des réductions doit être conservé : remise VIP puis remise Pass 3 jours.
Après un paiement réussi, le statut de la réservation devient confirmed.
Une confirmation est envoyée par e-mail après la confirmation de la réservation.