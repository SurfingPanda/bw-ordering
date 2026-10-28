# Payments and Grab courier

Planning notes for the next checkout and delivery implementation on `dev`.

## Current state on `dev`

- Orders support manual QR Ph, cash for pickup, and optional PayMongo hosted checkout.
- PayMongo hosted checkout is optional and enabled when its backend secret is configured. Its methods can include GCash, cards, Maya, and GrabPay.
- PayMongo webhooks and order reconciliation update the payment status.
- Delivery currently uses the shop's configured flat standard/express fees. There is no Grab courier booking or tracking integration on this branch.

**GrabPay and Grab courier are separate:** GrabPay is a payment method; GrabExpress is the courier service.

## Proposed implementation

1. **Choose the payment gateway.** Keep PayMongo, which is already implemented on `dev`, or select Xendit before changing the checkout flow. Do not mix gateway credentials or webhook formats.
2. **Connect GrabExpress on the backend.** Keep Grab credentials server-side. Add a courier service to request a delivery quote from the selected fulfillment store to the customer's address.
3. **Show the live quote at checkout.** Display the courier fee and estimated delivery time. Recheck the quote and final price on the server when the order is placed.
4. **Create the courier booking at the agreed order stage.** Decide whether booking happens after payment confirmation, staff acceptance, or both. Keep failed bookings retryable without duplicating a trip.
5. **Track the delivery.** Save the provider's booking/tracking reference and status. Process provider status callbacks idempotently and show useful updates to the customer and staff.
6. **Keep a fallback.** If Grab quoting or booking is unavailable, either offer the existing flat-fee delivery or temporarily disable courier delivery; choose the policy before implementation.

## Decisions and access needed

- Confirm PayMongo or Xendit as the payment gateway for `dev`.
- Confirm GrabExpress API/partner access and provide sandbox credentials through the backend environment, not in this file or frontend variables.
- Provide pickup details for each store, service area, parcel dimensions/weight rules, and any delivery restrictions.
- Decide whether the Grab fee is included in the order total and when the trip should be booked.
- Confirm the fallback policy and which delivery statuses should appear in the order history.

## Implementation checks

- A changed address/store refreshes the quote; expired quotes cannot be used to place an order.
- The server calculates and stores the final payment and courier amounts.
- Duplicate payment callbacks or courier callbacks do not duplicate status changes or bookings.
- Failed/expired payments do not create an unintended courier booking.
- Pickup orders bypass courier quoting and delivery fees.
- Credentials and full payment details never enter frontend code or logs.
