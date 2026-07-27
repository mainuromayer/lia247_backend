# Project Status: Gap Analysis (Client vs. Implementation)

Based on the **Final Understanding** requirements from the client (Lea/EmUL) and the current state of the `lia247_backend` codebase, here is a list of features and logic that are currently **MISSING** or **INCOMPLETE**.

---

### 1. Payment System (Stripe Logic)
*   **Card Hold (Authorization):** ❌ **Missing.** 
    *   In `RideController@requestRide`, there is a comment about fund verification, but it only checks if a default payment method exists. It does **not** perform a Stripe `PaymentIntent` with `capture_method => 'manual'`.
*   **Automatic Charge (Capture):** ❌ **Missing.** 
    *   In `RideController@completeRide`, the system credits the rider's local wallet but does **not** trigger the Stripe capture to actually take money from the customer's card.
*   **No Cash Option:** ✅ **Partially Done.** 
    *   Validation exists to require a card, but the actual transaction flow is not yet automated.

### 2. Ride Fare & GPS Logic
*   **Fixed Fare:** ❌ **Missing.** 
    *   The client wants the price to be "Locked" after confirmation. Currently, `completeRide` **recalculates** the fare based on the actual distance, which contradicts the "Fixed Fare" requirement.
*   **GPS Shortest Route:** ❌ **Missing.** 
    *   The `estimate` and `requestRide` methods take the `distance` as a raw input from the frontend. There is no backend verification or calculation using Google Maps API for the "shortest route."
*   **Broken Estimate API:** ⚠️ **Critical Bug.** 
    *   The `RideController@estimate` method uses undefined variables (`$baseFare`, `$pricePerMile`, `$vatPercentage`), which will cause a 500 error when called.

### 3. Wallet & Distribution System
*   **Admin Wallet Credit:** ❌ **Incomplete.** 
    *   The `commission_amount` is calculated and saved in the `Ride` model, but it is **not** credited to an Admin/System Wallet via `WalletService`.
*   **Auto-Distribution Flow:** ❌ **Missing.** 
    *   The "Admin Wallet First -> Then Split" logic is not implemented. Currently, it directly (and only) credits the Rider's wallet.
*   **VAT Logic:** ❌ **Incomplete.** 
    *   VAT is calculated in the fare, but there is no dedicated logic to move this 10% VAT into a specific tax/admin account for reporting.

### 4. Extra Features (The 2 items you are planning to do)
*   **Driver Arrival Countdown Timer:** ❌ **Missing.** 
    *   There is no backend logic or specific status/field to track the arrival countdown once a rider is "ready."
*   **Cancellation After Timer:** ❌ **Missing.** 
    *   While a basic `noShowCancel` exists (5-minute hardcoded check), the dynamic cancellation logic with specific fees ($100 extra cost items) is not yet integrated into the main flow.

### 5. Automated System (Payouts)
*   **Manual Payout vs Auto:** ❌ **Incomplete.** 
    *   The system uses a local wallet balance. There is no automated logic to trigger Stripe Payouts (Transfers) to Riders/Restaurants once the Admin Wallet receives funds.

---

### Recommendation for Next Steps:
1.  **Fix the Estimate API:** Define the settings variables properly.
2.  **Lock the Fare:** Update `requestRide` to save the fixed fare and `completeRide` to use that saved fare instead of recalculating.
3.  **Implement Stripe Hold/Capture:** This is the biggest gap. Without this, the system is not "Cashless" or "Automated."
4.  **Admin Wallet Logic:** Ensure every ride/order credits the `commission_amount` to a system-owned wallet.

> [!NOTE]
> The current system has the "skeleton" for wallets and rides, but the "muscles" (Stripe automation and locked pricing logic) are currently absent.
