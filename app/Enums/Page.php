<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;
use GuzzleHttp\Psr7\Header;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class Page extends Enum
{
    const CommonPage = 'common_page';
    const HomePage = 'home_page';
    const AboutPage = 'about_page';
    const BuyPage = 'buy_page';
    const SellPage = 'sell_page';

    const MasterClassPage = 'master_class_page';
    const InsightPage = 'insight_page';
    const FoodAndDiningPage = 'food_and_dining_page';
    const ShoppingPage = 'shopping_page';
    const GuideAndTipPage = 'guide_and_tip_page';
    const GuideToOahuPage = 'guide_to_oahu_page';

    const RentalPage = 'rental_page';
    const EventPage = 'event_page';
    const ThingsToDoPage = 'things_to_do_page';
    const CouponAndDealsPage = 'coupon_and_deals_page';
}
