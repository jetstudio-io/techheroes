<?php
/**
 * Onibi
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/LICENSE-M1.txt
 *
 * @category   Onibi
 * @package    Onibi_All
 * @copyright  Copyright (c) 2009-2010 Onibi (http://www.onibi.fr)
 * @license    http://store.onibi.fr/license.txt
 */

class Onibi_All_Helper_Data extends Mage_Core_Helper_Abstract
{    
    public function checkValid($observer)
    {    
        $modules = Mage::getConfig()->getNode('modules')->children();
        $modulesArray = (array)$modules;
        $keys = array();
        foreach($modulesArray as $key => $module){
            if(strstr($key,'Onibi_') && Mage::helper('core')->isModuleEnabled($key)){
                $keys[] = $module->key;
            }
        }
        
        if(count($keys)){
            $layout = Mage::app()->getLayout();
            $content = $layout->getBlock('content');
            $block = $layout->createBlock('onibi_all/valid');
            $block->setKeys($keys);
            if($content){
                $content->insert($block);
            }
        }
    }
    
    function getDomain()
    {
        $domain = $_SERVER['SERVER_NAME'];
        $temp = explode('.', $domain);
        $exceptions = array(
                'co.uk',
                'com.au'
        );
    
        $count = count($temp);
        $last = $temp[($count-2)] . '.' . $temp[($count-1)];
    
        if(in_array($last, $exceptions))    {
            $new_domain = $temp[($count-3)] . '.' . $temp[($count-2)] . '.' . $temp[($count-1)];
        }
        else    {
            $new_domain = $temp[($count-2)] . '.' . $temp[($count-1)];
        }
    
        return $new_domain;
    }
    
    function checkEntry($module, $domain, $serial)
    {
        $key = sha1(base64_decode($module));
      
        if(sha1($key.$domain) == $serial)   {
            return true;
        }

        return false;
    }
    
    public function canRun($module)
    {
        if(base64_decode($module) != 'Onibi_Free'){
            $temp = Mage::getStoreConfig(base64_decode($module).'/general/' . base64_decode('c2VyaWFs'));
          
            $original = $this->checkEntry($module, $_SERVER['SERVER_NAME'], $temp);
            $wildcard = $this->checkEntry($module, $this->getDomain(), $temp);
    
            if(!$original && !$wildcard) {
                return false;
            }
        }
        return true;
    }
}