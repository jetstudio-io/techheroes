<?php
/**
 *    OneStepCheckout main block
 *    @author Jone Eide <mail@onestepcheckout.com>
 *    @copyright Jone Eide <mail@onestepcheckout.com>
 *
 */

class Onibi_All_Block_Valid extends Mage_Core_Block_Template
{
    public function _toHtml()
    {
        $helper = Mage::helper('onibi_all');
        $message = false;
      
        $html = '';
        $modules =  array();
        
        foreach($this->getKeys() as $key){
            if(!$helper->canRun($key)) {
                $modules[] = $key;
            }
        }
        
        if(count($modules)){
            $html .= base64_decode('PGRpdiBzdHlsZT0iYm9yZGVyOiAzcHggc29saWQgcmVkOyB0ZXh0LWFsaWduOmNlbnRlcjsgcGFkZGluZzogNXB4OyBtYXJnaW4tYm90dG9tOiAxNXB4OyBtYXJnaW4tdG9wOiAxNXB4OyI+');
            $html .= $this->__(base64_decode('VGhlIGZvbGxvd2luZyhzKSBtb2R1bGUocykgbmVlZCBhIHZhbGlkIHNlcmlhbCA6IDxici8+')); 
            
            foreach($modules as $module){
                $html .= '<b>Onibi_'.ucfirst(base64_decode($module)).'</b><br>';
            }
            
            $html .= $this->__(base64_decode('PGJyLz5QbGVhc2UgZW50ZXIgYSB2YWxpZCBzZXJpYWwgZm9yIHRoZSBkb21haW4gIiVzIiBpbiB5
                                              b3VyIGFkbWluaXN0cmF0aW9uIHBhbmVsLiBJZiB5b3UgZG9uJ3QgaGF2ZSBvbmUsIHBsZWFzZSBw
                                              dXJjaGFzZSBhIHZhbGlkIGxpY2Vuc2UgZnJvbSA8YSBocmVmPSJodHRwOi8vc3RvcmUub25pYmku
                                              ZnIiPmh0dHA6Ly9zdG9yZS5vbmliaS5mcjwvYT48YnIvPjxici8+SWYgeW91IGhhdmUgZW50ZXJl
                                              ZCBhIHZhbGlkIHNlcmlhbCwgcGxlYXNlIGNvbnRhY3QgdXMu'), $_SERVER['SERVER_NAME']);
            $html .= base64_decode('PC9kaXY+');
        }
        
        return $html;
    }
}