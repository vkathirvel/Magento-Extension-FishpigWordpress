public function viewAction()
{
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
            'link_rel', 
            $post->getCommentFeedUrl(), 
            sprintf('rel="alternate" type="application/rss+xml" title="%s &raquo; %s Comments Feed"', 
                Mage::helper('wordpress')->getWpOption('blogname'), 
                $post->getPostTitle()
            )
        );

        if (Mage::helper('wordpress')->getWpOption('default_ping_status') === 'open' && $post->getPingStatus() == 'open') {
            $headBlock->addItem('link_rel', Mage::helper('wordpress')->getBaseUrl() . 'xmlrpc.php', 'rel="pingback"');              
        }
    }

    if ($post->getTypeInstance()->hasArchive()) {
        $this->addCrumb($post->getPostType() . '_archive', array('label' => $post->getTypeInstance()->getName(), 'link' => $post->getTypeInstance()->getArchiveUrl()));
    }

    if ($post->isType('page') && (int)$post->getId() === (int)Mage::helper('wordpress/router')->getHomepagePageId()) {
        $post->setCanonicalUrl(Mage::helper('wordpress')->getUrl());

        if (Mage::helper('wordpress')->getBlogRoute() === '') {
            $this->_crumbs = array();
        }
        else {
            array_pop($this->_crumbs);
        }
    }
    else if ($post->getTypeInstance()->isHierarchical()) {
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

        foreach($posts as $buffer) {
            $this->addCrumb('post_' . $buffer->getId(), array('label' => $buffer->getPostTitle(), 'link' => $buffer->getUrl()));
        }
    }
    else if ($post->getTypeInstance()->isTaxonomySupported('category')) {
        if ($term = $post->getParentTerm('category')) {
            $terms = array();

            while($term) {
                array_unshift($terms, $term);
                $term = $term->getParentTerm();
            }

            foreach($terms as $term) {
                $this->addCrumb('post_' . $term->getTaxonomyType() . '_' . $term->getId(), array('label' => $term->getName(), 'link' => $term->getUrl()));
            }
        }
    }

    $this->addCrumb('post', array('label' => $post->getPostTitle()));

    $this->renderLayout();
}