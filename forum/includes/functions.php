<?php

if (!function_exists('formatNumber')) {
    /**
     * Formats a number into a more readable K/M format.
     * e.g., 1000 -> 1K, 1500000 -> 1.5M
     *
     * @param int|float $number The number to format.
     * @return string The formatted number.
     */
    function formatNumber($number) {
        if (!is_numeric($number)) {
            return '0'; // Or handle error appropriately
        }
        if ($number >= 1000000) {
            return round($number / 1000000, 1) . 'M';
        } elseif ($number >= 1000) {
            return round($number / 1000, 1) . 'K';
        }
        return (string)$number;
    }
}

if (!function_exists('getIconForCategory')) {
    /**
     * Gets a Font Awesome icon name based on a category name.
     *
     * @param string $categoryName The name of the category.
     * @return string The Font Awesome icon name (without 'fa-').
     */
    function getIconForCategory($categoryName) {
        $categoryName = strtolower(trim($categoryName));
        switch ($categoryName) {
            case 'programming':
                return 'code';
            case 'web development':
                return 'laptop-code';
            case 'design':
                return 'paint-brush';
            case 'graphic design':
                return 'palette';
            case 'networking':
                return 'network-wired';
            case 'cybersecurity':
            case 'security':
                return 'shield-alt';
            case 'general discussion':
                return 'comments';
            case 'tutorials':
                return 'graduation-cap';
            case 'news & announcements':
                return 'bullhorn';
            // Add more cases as needed for your categories
            default:
                return 'tag'; // Default icon
        }
    }
}

// You might want to add other global functions here in the future.

?>
