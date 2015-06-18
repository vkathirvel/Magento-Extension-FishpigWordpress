<?php

/**
 * Optimiseweb FishpigWordpress Post Controller
 *
 * @package     Optimiseweb_FishpigWordpress
 * @author      Kathir Vel (vkathirvel@gmail.com)
 * @copyright   Copyright (c) 2015 Kathir Vel
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
require_once Mage::getModuleDir('controllers', 'Fishpig_Wordpress') . DS . 'PostController.php';

class Optimiseweb_FishpigWordpress_PostController extends Fishpig_Wordpress_PostController
{

    /**
     * 
     */
    public function viewAction()
    {
        $fishpigWordpressModuleVersion = Mage::getConfig()->getNode('modules/Fishpig_Wordpress/version');

        if ($fishpigWordpressModuleVersion < 4) {
            parent::viewAction();
        } else {

            $post = $this->getEntityObject();

            $layoutHandles = array(
                    'wordpress_post_view',
                    'wordpress_' . $post->getPostType() . '_view',
                    'wordpress_' . $post->getPostType() . '_view_' . $post->getId(),
                    'wordpress_post_view_' . strtoupper($post->getPostType()),
            );

            if ($post->getTypeInstance()->isHierarchical()) {
                $buffer = $post->getParentPost();

                while ($buffer) {
                    $layoutHandles[] = 'wordpress_' . $post->getPostType() . '_view_parent_' . $buffer->getId();

                    // Legacy
                    if ($post->isType('page')) {
                        $layoutHandles[] = 'wordpress_' . $post->getPostType() . '_parent_' . $buffer->getId();
                    }

                    $buffer = $buffer->getParentPost();
                }
            }

            $this->_addCustomLayoutHandles($layoutHandles);
            $this->_initLayout();
            $this->_title(strip_tags($post->getPostTitle()));

            if (($headBlock = $this->getLayout()->getBlock('head')) !== false) {
                $headBlock->addItem(
                    'link_rel', $post->getCommentFeedUrl(), sprintf('rel="alternate" type="application/rss+xml" title="%s &raquo; %s Comments Feed"', Mage::helper('wordpress')->getWpOption('blogname'), $post->getPostTitle()
                    )
                );

                if (Mage::helper('wordpress')->getWpOption('default_ping_status') === 'open' && $post->getPingStatus() == 'open') {
                    $headBlock->addItem('link_rel', Mage::helper('wordpress')->getBaseUrl() . 'xmlrpc.php', 'rel="pingback"');
                }
            }

            if ($post->getTypeInstance()->hasArchive()) {
                $this->addCrumb($post->getPostType() . '_archive', array('label' => $post->getTypeInstance()->getName(), 'link' => $post->getTypeInstance()->getArchiveUrl()));
            }

            if ($post->isType('page') && (int) $post->getId() === (int) Mage::helper('wordpress/router')->getHomepagePageId()) {
                $post->setCanonicalUrl(Mage::helper('wordpress')->getUrl());

                if (Mage::helper('wordpress')->getBlogRoute() === '') {
                    $this->_crumbs = array();
                } else {
                    array_pop($this->_crumbs);
                }
            } else if ($post->getTypeInstance()->isHierarchical()) {
                $posts = array();
                $buffer = $post;

                while ($buffer) {
                    $this->_title(strip_tags($buffer->getPostTitle()));
                    $posts[] = $buffer;
                    $buffer = $buffer->getParentPost();
                }

                $posts = array_reverse($posts);

                // Remove current post from end array
                array_pop($posts);

                foreach ($posts as $buffer) {
                    $this->addCrumb('post_' . $buffer->getId(), array('label' => $buffer->getPostTitle(), 'link' => $buffer->getUrl()));
                }
            } else if ($post->getTypeInstance()->isTaxonomySupported('category')) {
                if ($term = $post->getParentTerm('category')) {
                    $terms = array();

                    while ($term) {
                        array_unshift($terms, $term);
                        $term = $term->getParentTerm();
                    }

                    foreach ($terms as $term) {
                        $this->addCrumb('post_' . $term->getTaxonomyType() . '_' . $term->getId(), array('label' => $term->getName(), 'link' => $term->getUrl()));
                    }
                }
            }

            $this->addCrumb('post', array('label' => $post->getPostTitle()));

            if ($post->getMetaValue('_wp_page_template')) {
                $template = $post->getMetaValue('_wp_page_template');
                // This is the folder in Magento template
                $path = 'page/';
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
            }

            $this->renderLayout();
        }
    }

}
