<?php

/*
 * Copyright (C) 2017 Andy
 *

  /**
 * Description of matomo
 *
 * @author Andy
 */

class zcObserverMatomoGaAlternative extends base
{

    /**
     * @var string
     */
    var $body_id = '';

    /**
     * @var string
     */
    var $matomo_event = '';

    /**
     * @var bool
     */
    private $debug = false;

    /**
     * @var array
     */
    private $debugMessages = array();

    /**
     * zcObserverMatomoGaAlternative constructor.
     */
    function __construct()
    {
        $this->body_id = ($this_is_home_page) ? 'indexHome' : str_replace('_', '', $_GET['main_page']);
        $this->debugMessages[] = __METHOD__ . ' :: ' . __LINE__ . ' :: ' . $_SERVER['REQUEST_URI'];
        $this->debugMessages[] = __METHOD__ . ' :: ' . __LINE__ . ' :: ' . $this->body_id;

        $this->replace_characters = array("$", ",");
        $attach_array[] = 'NOTIFY_HTML_HEAD_END';
        if (defined('MATOMO_GA_ALTERNATIVE_ENABLE_ECOMMERCE') && MATOMO_GA_ALTERNATIVE_ENABLE_ECOMMERCE == 'true') {
//            $attach_array[] = 'NOTIFY_HEADER_END_SHOPPING_CART';
//            $attach_array[] = 'NOTIFY_HEADER_START_PRODUCT_INFO';
//            $attach_array[] = 'NOTIFY_HEADER_END_CHECKOUT_SUCCESS';
            $attach_array[] = 'NOTIFY_FOOTER_END';
        }

        $this->attach($this, $attach_array);

        $this->set_matomo_head();
        $this->matomo_event();
    }

    function matomo_event()
    {
        global $currencies, $db;
        $this->debugMessages[] = __METHOD__ . ' :: ' . __LINE__ . ' :: ' . $this->body_id;
        if (strpos($this->body_id, 'productinfo') !== false) {
            $this->debugMessages[] = __METHOD__ . ' :: ' . __LINE__ . ' :: ' . $this->body_id;
            //echo __FILE__ . ' :: ' . __METHOD__ . ' :: ' . PHP_EOL;
            $products_price = '';
            $products_price = filter_var(strip_tags(zen_get_products_display_price((int) $_GET['products_id'])), FILTER_SANITIZE_NUMBER_FLOAT);
            
            if (defined('MATOMO_GA_ALTERNATIVE_MODEL_SKU') && MATOMO_GA_ALTERNATIVE_MODEL_SKU == 'true') {
                $sku = zen_get_products_model( (int) $_GET['products_id'] );
	    }
	    else {
	        $sku = (int) $_GET['products_id'];
	    }

            $this->matomo_event = '
_paq.push([\'setEcommerceView\',
    "' . $sku . '", 
    "' . htmlentities(zen_get_products_name((int) $_GET['products_id'])) . '",
    "' . htmlentities(zen_get_products_category_id((int) $_GET['products_id'])) . '",
    ' . $products_price . ' 
]);' . PHP_EOL;
        }

        if ($this->body_id == 'shoppingcart') {
            $this->debugMessages[] = __METHOD__ . ' :: ' . __LINE__;
            $products = $_SESSION['cart']->get_products();
            // An addEcommerceItem push should be generated for each cart item, even the products not updated by the current "Add to cart" click.
            $matomo_string = '';
            $replace_characters = array("$", ",");
            for ($i = 0, $n = sizeof($products); $i < $n; $i++) {
                $products_price = filter_var(strip_tags(zen_get_products_display_price((int) $_GET['products_id'])), FILTER_SANITIZE_NUMBER_FLOAT);
                $this->debugMessages[] = __LINE__ . ' :: ' . zen_get_products_display_price((int) $products[$i]['id']);
                $matomo_string .= "_paq.push(['addEcommerceItem',";
                $matomo_string .= '"' . $products[$i]['id'] . '",';
                $matomo_string .= '"' . htmlentities(zen_get_products_name($products[$i]['id'])) . '",';
                $matomo_string .= '"' . htmlentities(zen_get_products_category_id($products[$i]['id'])) . '",';
                $matomo_string .= '"' . $products_price . '",';
                $matomo_string .= '"' . $products[$i]['quantity'] . '"';
                $matomo_string .= ']);' . PHP_EOL;
            }

            // Pass the Cart's Total Value as a numeric parameter
            $this->debugMessages[] = __METHOD__ . ' :: ' . __LINE__ . ' :: ' . $matomo_string;
            $matomo_string .= '_paq.push([\'trackEcommerceCartUpdate\',' . str_replace(['$', ','], "", $currencies->format($_SESSION['cart']->show_total())) . "']);" . PHP_EOL;
            $this->matomo_event = $matomo_string;
        }

        if ($this->body_id == 'checkoutsuccess') {

            global $db;

            $orders_query = "SELECT * FROM " . TABLE_ORDERS . "
                 WHERE customers_id = :customersID:
                 ORDER BY date_purchased DESC LIMIT 1";
            $orders_query = $db->bindVars($orders_query, ':customersID:', $_SESSION['customer_id'], 'integer');
            $orders = $db->Execute($orders_query);
            $orders_id = $orders->fields['orders_id'];

//            ini_set('xdebug.var_display_max_depth', 10);
//            ini_set('xdebug.var_display_max_children', 256);
//            ini_set('xdebug.var_display_max_data', 1024);
//            echo '<pre>';
//            var_dump($this_order->products);
//            echo '</pre>';
//            die;

            $orders_products_query = "select * from " . TABLE_ORDERS_PRODUCTS . " where orders_id = '" . (int) $orders_id . "'";
            $orders_products = $db->Execute($orders_products_query);
            $javascript_string = '';
            while (!$orders_products->EOF) {
                if (defined('MATOMO_GA_ALTERNATIVE_MODEL_SKU') && MATOMO_GA_ALTERNATIVE_MODEL_SKU == 'true') {
                    $sku = zen_get_products_model( (int) $orders_products->fields['products_id'] );
	    	}
	        else {
	            $sku = (int) (int) $orders_products->fields['products_id'];
	    	}

                $javascript_string .= "_paq.push(['addEcommerceItem'," . PHP_EOL;
                $javascript_string .= '"' . $sku . '",' . PHP_EOL;
                $javascript_string .= '"' . htmlentities($orders_products->fields['products_name']) . '",' . PHP_EOL;
                $javascript_string .= '"' . htmlentities(zen_get_products_category_id((int)$orders_products->fields['products_id'])) . '",' . PHP_EOL;
                $javascript_string .= '"' . $orders_products->fields['final_price'] . '",' . PHP_EOL;
                $javascript_string .= '"' . $orders_products->fields['products_quantity'] . '"' . PHP_EOL;
                $javascript_string .= ']);' . PHP_EOL;
                $orders_products->MoveNext();
            }

            $orders_total_query = "select * from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int) $orders_id . "'";
            $orders_total = $db->Execute($orders_total_query);

            $total_value = 0;
            $subtotal_value = 0;
            $tax_value = 0;
            $shipping_value = 0;
            $other_value = 0;
            while (!$orders_total->EOF) {
                switch ($orders_total->fields['class']) {
                    case 'ot_total':
                        $total_value = $orders_total->fields['value'];
                        break;
                    case 'ot_shipping':
                        $shipping_value = $orders_total->fields['value'];
                        break;
                    case 'ot_subtotal':
                        $subtotal_value = $orders_total->fields['value'];
                        break;
                    case 'ot_taxify':
                        $tax_value = $orders_total->fields['value'];
                        break;
                    default:
                        $other_value = + $orders_total->fields['value'];
                        break;
                }
                $orders_total->MoveNext();
            }

            $javascript_string .= '
_paq.push([\'trackEcommerceOrder\',
    "' . (int) $orders_id . '",
    ' . ($total_value - $other_value) . ',
    ' . $subtotal_value . ',
    ' . $tax_value . ',
    ' . $shipping_value . '        
]);' . PHP_EOL;

//            echo '<pre>';
//            var_dump($this_order->totals);
//            echo '</pre>';
//            die;
// Order Array - Parameters should be generated dynamically
            $this->matomo_event = $javascript_string;
        }
    }

    function update(&$class, $eventID, $paramsArray = array())
    {
        global $currencies, $db;

        if ($eventID == 'NOTIFY_FOOTER_END') {
            if (defined('MATOMO_GA_ALTERNATIVE_ENABLE_IMAGE_TRACKING') && MATOMO_GA_ALTERNATIVE_ENABLE_IMAGE_TRACKING == 'true') {
                echo $this->return_image_tracker();
            }

            if (defined('MATOMO_GA_ALTERNATIVE_ENABLE_DEBUG') && MATOMO_GA_ALTERNATIVE_ENABLE_DEBUG == 'true') {
                $this->errorlog($this->debugMessages);
            }
        }
        if ($eventID == 'NOTIFY_HTML_HEAD_END') {
            $matomo_string = $this->return_matomo();
            $this->debugMessages[] = __METHOD__ . ' :: ' . __LINE__ . ' :: ' . $matomo_string;
            echo $matomo_string;
//            echo '<pre>';
//            echo $text;
//            echo '</pre>';
//            var_dump(__LINE__);die;
        }

        if ($eventID == 'NOTIFY_HEADER_END_SHOPPING_CART') {
            // An addEcommerceItem push should be generated for each cart item, even the products not updated by the current "Add to cart" click.
            $products = $_SESSION['cart']->get_products();

            for ($i = 0, $n = sizeof($products); $i < $n; $i++) {
                $products_price = filter_var(strip_tags(zen_get_products_display_price((int) $_GET['products_id'])), FILTER_SANITIZE_NUMBER_FLOAT);
                $javascript_string .= "_paq.push(['addEcommerceItem'," . PHP_EOL;
                $javascript_string .= '"' . $products[$i]['id'] . '",' . PHP_EOL;
                $javascript_string .= '"' . htmlentities(zen_get_products_name($products[$i]['id'])) . '",' . PHP_EOL;
                $matomo_string .= '"' . htmlentities(zen_get_products_category_id($products[$i]['id'])) . '",';
                $javascript_string .= '"' . $products_price . '",' . PHP_EOL;
                $javascript_string .= '"' . $products[$i]['quantity'] . '",' . PHP_EOL;
                $javascript_string .= ']);' . PHP_EOL;
            }
// Pass the Cart's Total Value as a numeric parameter
            $javascript_string .= '_paq.push([\'trackEcommerceCartUpdate\', ' . str_replace(['$', ','], "", $currencies->format($_SESSION['cart']->show_total())) . ']);' . PHP_EOL;
            $this->matomo_event = $javascript_string;
            $this->debugMessages[] = $javascript_string;
        }
    }

    function set_matomo_head()
    {
        $this->matomo_head = '
<!-- Matomo -->
<script>
var _paq = window._paq = window._paq || [];
';
        //       $this->debugMessages[] = __METHOD__ . ' :: ' . __LINE__ . ' :: ' . $this->matomo_head;
    }

    function return_matomo_footer()
    {
        $matomoSetUserID = '';
        if (!empty($_SESSION['customer_id'])) {
            $matomoSetUserID = "_paq.push(['setUserId', '" . (int) $_SESSION['customer_id'] . "']);";
        }
        $matomo = '_paq.push([\'trackPageView\']);' . PHP_EOL;
        if (defined('MATOMO_GA_ALTERNATIVE_ENABLE_CLIENT_SIDE_DONOTTRACK_DETECTION') && MATOMO_GA_ALTERNATIVE_ENABLE_CLIENT_SIDE_DONOTTRACK_DETECTION == 'true') {
            $matomo .= '_paq.push(["setDoNotTrack", true]);';
        }
        $matomo .= $matomoSetUserID . PHP_EOL;
        $matomo .= '_paq.push([\'enableLinkTracking\']);
_paq.push([\'enableHeartBeatTimer\']);

(function() {
    var u="' . MATOMO_GA_ALTERNATIVE_URL . '";
    _paq.push([\'setTrackerUrl\', u+\'matomo.php\']);
    _paq.push([\'setSiteId\', \'' . MATOMO_GA_ALTERNATIVE_SITE_ID . '\']);
    var d=document, g=d.createElement(\'script\'), s=d.getElementsByTagName(\'script\')[0];
    g.async=true; g.src=u+\'matomo.js\'; s.parentNode.insertBefore(g,s);
})();
</script>
<!-- End Matomo Code -->
';

        $this->debugMessages[] = $matomo;
        return $matomo;
    }

    function return_image_tracker()
    {
        $image_tracking = '';
        $image_tracking .= '<!-- Matomo Image Tracker-->' . PHP_EOL;
        $image_tracking .= '<noscript><img referrerpolicy="no-referrer-when-downgrade" src="' . MATOMO_GA_ALTERNATIVE_URL . '/?idsite=' . MATOMO_GA_ALTERNATIVE_SITE_ID . '&amp;rec=1" style="border:0" alt="" /></noscript>' . PHP_EOL;
        $image_tracking .= '<!-- End Matomo Image -->' . PHP_EOL;
        $this->debugMessages[] = $image_tracking;
        return $image_tracking;
    }

    function return_matomo_event()
    {
        return $this->matomo_event;
    }

    function return_matomo_head()
    {
        return $this->matomo_head;
    }

    function return_matomo()
    {
        $matomo = '';
        if (defined('MATOMO_GA_ALTERNATIVE_ENABLE_JAVASCRIPT') && MATOMO_GA_ALTERNATIVE_ENABLE_JAVASCRIPT == 'true') {
            $matomo = $this->return_matomo_head();
            $matomo .= $this->return_matomo_event();
            $matomo .= $this->return_matomo_footer();
        }
        return $matomo;
    }

    /**
     * @param array $errorMessages
     */
    private function errorLog($errorMessages = array())
    {

        $logDir = defined('DIR_FS_LOGS') ? DIR_FS_LOGS : DIR_FS_SQL_CACHE;
        $message = date('M-d-Y h:i:s') .
                "\n=================================\n\n";
        foreach ($errorMessages as $errorMessage) {
            $message .= $errorMessage . "\n\n";
        }

        $file = $logDir . '/' . 'Matomo_GA_Alternative_Debug_' . $_SERVER['REMOTE_ADDR'] . '_' . $this->body_id . '_' . time() . '.log';
        if ($fp = @fopen($file, 'a')) {
            fwrite($fp, $message);
            fclose($fp);
        }
    }

}

/* 
 insert into configuration set configuration_title = 'Matomo URL', configuration_key = 'MATOMO_URL', configuration_value = 'https://antidote.usmarketingpros.com/', configuration_description = 'URL where to point for matomo', configuration_group_id = 10, sort_order = 99, date_added = now(), configuration_tab = 'General';
 insert into configuration set configuration_title = 'Matomo Site ID', configuration_key = 'MATOMO_SITE_ID', configuration_value = '10', configuration_description = 'Site id for matomo', configuration_group_id = 10, sort_order = 99, date_added = now(), configuration_tab = 'General';
 */
