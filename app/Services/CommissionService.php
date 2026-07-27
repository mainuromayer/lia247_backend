<?php

namespace App\Services;

class CommissionService
{
    /**
     * Calculate Food Delivery Distribution
     * Vendor: (Price - Commission)
     * Delivery Man: Delivery Charge
     * Admin: Commission
     */
    public static function calculateFoodSplit($foodPrice, $deliveryCharge, $discountAmount = 0, $voucherOwner = 'admin', $commissionPercent = 10, $freeDeliveryCoveredBy = null, $adminContribution = 0, $vendorContribution = 0)
    {
        $adminCommission = ($foodPrice * $commissionPercent) / 100;
        
        $vendorPart = $foodPrice - $adminCommission;
        $adminPart = $adminCommission;

        if ($voucherOwner === 'vendor') {
            // Vendor bears the loss
            $vendorPart -= $discountAmount;
        } else {
            // Admin bears the loss
            $adminPart -= $discountAmount;
        }

        // Handle Free Delivery Contributions
        // Even if deliveryCharge is 0 (paid by campaign), the delivery man still gets the full fee.
        // We need to know what the ORIGINAL delivery fee was.
        // However, in our system, if it's free delivery, $deliveryCharge passed here will be 0.
        // The contributions will cover the delivery man's fee.
        
        $deliveryManPart = $deliveryCharge + $adminContribution + $vendorContribution;

        // Subtract contributions from the respective parties
        $adminPart -= $adminContribution;
        $vendorPart -= $vendorContribution;

        return [
            'vendor_amount' => $vendorPart,
            'delivery_man_amount' => $deliveryManPart,
            'admin_amount' => $adminPart,
        ];
    }

    /**
     * Calculate Ride Share Distribution
     * Rider/Driver: (Fare * DriverPayoutPercentage)
     * Admin: (Fare * (100 - DriverPayoutPercentage))
     */
    public static function calculateRideSplit($fare, $driverPayoutPercentage = 75)
    {
        $riderPart = ($fare * $driverPayoutPercentage) / 100;
        $adminPart = $fare - $riderPart;

        return [
            'rider_amount' => $riderPart,
            'admin_amount' => $adminPart,
        ];
    }
}
