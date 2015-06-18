<?php

/**
 * Optimiseweb FishpigWordpress Page Controller
 *
 * @package     Optimiseweb_FishpigWordpress
 * @author      Kathir Vel (vkathirvel@gmail.com)
 * @copyright   Copyright (c) 2015 Kathir Vel
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
require_once Mage::getModuleDir('controllers', 'Fishpig_Wordpress') . DS . 'PageController.php';

class Optimiseweb_FishpigWordpress_PageController extends Fishpig_Wordpress_PageController
{

    /**
     * Override Magento config by setting a single column template
     * if specified in Page edit of WP Admin
     *
     * @return $this
     */
    protected function _setPageViewTemplate()
    {

        $fishpigWordpressModuleVersion = Mage::getConfig()->getNode('modules/Fishpig_Wordpress/version');

        if ($fishpigWordpressModuleVersion > 4) {
            parent::_setPageViewTemplate();
        } else {
            $page = $this->_initPage();

            $template = $page->getMetaValue('_wp_page_template');

            // This is the folder in Magento template
            $path = 'page/';

            //preg_match('/([^\/]*)\.php$/', $template, $match);
            //$filename = $path . $match[1] . '.phtml';
            // The extension is replaced with .phtml,
            // meaning that the directory structure is preserved.
            // (page-templates/templ.php will become $path . page-templates/templ.phtml)
            $filename = $path . str_replace('.php', '.phtml', $template);

            // This is to use Magento fallback system
            $params = array('_relative' => false);
            $area = $this->getLayout()->getBlock('root')->getArea();

            if ($area) {
                $params['_area'] = $area;
            }

            $templateName = Mage::getDesign()->getTemplateFilename($filename, $params);

            // If no other matches are found, Magento will eventually give a path in base/default, even if that template doesn't exist
            if (file_exists($templateName)) {
                $this->getLayout()->getBlock('root')->setTemplate($filename);
            }

            return $this;
        }
    }

}
