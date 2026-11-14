<?php
/** @copyright 2026 Tony Cradwick @license GPL-2.0-only */
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    exit;
}

final class AdminMenuIconsCatalog
{
    /** Font Awesome 6 Free solid icons, already supplied by Zen Cart 2.1+. */
    public static function icons(): array
    {
        $names = 'address-book arrows-rotate arrow-right arrow-right-arrow-left arrow-up-right-from-square '
            . 'bag-shopping ban barcode bars bell book bookmark box box-archive boxes-stacked briefcase '
            . 'bullhorn calendar calendar-check camera cart-shopping chart-bar chart-line chart-pie check '
            . 'circle-check circle-info circle-question clipboard clipboard-check clock cloud code coins '
            . 'comments credit-card database download envelope eye file file-csv file-image file-invoice '
            . 'file-invoice-dollar file-lines file-pdf filter flag folder folder-open gear gears gift globe '
            . 'heart house image images key language link list list-check location-dot lock magnifying-glass '
            . 'money-bill money-bill-transfer palette paperclip pen pencil percent phone plug plus print '
            . 'receipt rotate-left screwdriver-wrench shield-halved sliders star store table tags trash '
            . 'triangle-exclamation truck upload user user-check user-gear user-group users wallet '
            . 'wrench xmark';
        return explode(' ', $names);
    }

    /** Sparse starting assignments; all other entries deliberately remain blank. */
    public static function defaults(): array
    {
        return [
            'categoriesProductListing' => 'boxes-stacked', 'customers' => 'users',
            'orders' => 'cart-shopping', 'manufacturers' => 'tags', 'specials' => 'percent',
            'featured' => 'star', 'reviews' => 'comments', 'payment' => 'credit-card',
            'shipping' => 'truck', 'plugins' => 'plug', 'currencies' => 'coins',
            'languages' => 'language', 'couponAdmin' => 'gift', 'whosOnline' => 'eye',
            'toolsDisplayLogs' => 'file-lines', 'toolsAdminMenuIcons' => 'palette',
        ];
    }
}
