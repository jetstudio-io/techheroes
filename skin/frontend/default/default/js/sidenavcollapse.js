/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE_AFL.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   Codnitive
 * @package    Codnitive_Sidenav
 * @author     Hassan Barza <support@codnitive.com>
 * @copyright  Copyright (c) 2011 CODNITIVE Co. (http://www.codnitive.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

function expandMenu(parent) 
{
	var mode = parent.getElementsByTagName("ul")[0].getAttribute("expanded");
	mode = mode == 1;
	
	(mode) ? collapse(parent) : expand(parent);
}

function expand(parent) 
{
	parent.getElementsByTagName("ul")[0].style.display = "block";
	parent.getElementsByTagName("span")[0].style.backgroundPosition = "right center";
	parent.getElementsByTagName("ul")[0].setAttribute("expanded", "1");
}

function collapse(parent) 
{
	parent.getElementsByTagName("ul")[0].style.display = "none";
	parent.getElementsByTagName("span")[0].style.backgroundPosition = "left center";
	parent.getElementsByTagName("ul")[0].setAttribute("expanded", "0");
}