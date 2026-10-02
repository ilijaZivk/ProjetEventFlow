# EventFlow

Projet final B2 - Design Patterns & Clean Code.

## Prérequis

PHP 8.1 ou supérieur (enums, propriétés `readonly`). Aucun framework ni dépendance externe.

## Lancer l'application

```bash
php index.php
```

Deux scénarios sont joués : une cliente VIP payée par Stripe, puis un client Pass 3 jours payé par PayFast.

## Lancer les tests

```bash
php tests/run.php                  # tous les fichiers de test
php tests/characterization.php     # un seul fichier
```

| Fichier | Ce qu'il vérifie |
|---|---|
| `tests/characterization.php` | Comportement de bout en bout de `BookingService::confirm()` : totaux, validations, cas limites |
| `tests/PricingTest.php` | Politique tarifaire #102 : paliers VIP, Pass 3 jours, total jamais négatif |
| `tests/PaymentTest.php` | Paiements Stripe et PayFast #103 |
| `tests/ConfirmationTest.php` | Actions après confirmation #104 |
| `tests/MonitoringTest.php` | Supervision des paiements #105 |

## Organisation du code (`src/`)

| Rôle | Classes |
|---|---|
| Domaine | `Booking`, `BookingItem`, `Customer`, `Ticket`, `Money`, `CustomerType`, `PassType`, `BookingStatus` |
| Orchestration | `BookingService`, `EventFlowFactory` |
| Tarification | `PriceCalculator`, `PricingRule`, `VipTierDiscount`, `ThreeDayPassDiscount` |
| Paiement | `PaymentGateway`, `PaymentGateways`, `PaymentReceipt`, `PaymentFailedException`, `StripePaymentGateway`, `PayFastPaymentGateway` |
| Supervision | `MonitoredPaymentGateway`, `Logger`, `ConsoleLogger` |
| Après confirmation | `BookingConfirmedListener`, `SendConfirmationEmail`, `AwardLoyaltyPoints`, `TrackBookingConfirmed`, `SendConfirmationSms` |
| Persistance | `BookingRepository`, `ConsoleBookingRepository` |
| Clients externes fournis | `StripeClient`, `PayFastSdk`, `EmailService`, `SmsClient`, `LoyaltyService`, `AnalyticsClient` |

`src/PayFastSdk.php` et `src/StripeClient.php` n'ont pas été modifiés.

Voir aussi `AUDIT.md` (diagnostic initial) et `CONCEPTION.md` (décisions de conception).
