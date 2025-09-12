<?php
/**
 * @package     Content Shortcodes
 * @subpackage  plg_content_contentshortcodes
 * @author      Pranay
 * @copyright   Copyright (C) 2024. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Mail\Mail;

/**
 * Content Shortcodes Plugin
 *
 * @since  1.0.0
 */
class PlgContentContentshortcodes extends CMSPlugin
{
    /**
     * Load the language file on instantiation
     *
     * @var    boolean
     * @since  1.0.0
     */
    protected $autoloadLanguage = true;

    /**
     * Application object
     *
     * @var    \Joomla\CMS\Application\CMSApplication
     * @since  1.0.0
     */
    protected $app;

    /**
     * Database object
     *
     * @var    \Joomla\Database\DatabaseDriver
     * @since  1.0.0
     */
    protected $db;

    /**
     * Plugin that processes shortcodes in content
     *
     * @param   string   $context  The context of the content being passed to the plugin
     * @param   mixed    &$row     An object with a "text" property
     * @param   mixed    &$params  Additional parameters
     * @param   integer  $page     Optional page number
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function onContentPrepare($context, &$row, &$params, $page = 0)
    {
        // Don't run this plugin when the content is being indexed
        if ($context === 'com_finder.indexer') {
            return;
        }

        // Check if we have text to process
        if (empty($row->text)) {
            return;
        }

        // Load CSS if enabled
        if ($this->params->get('load_css', 1)) {
            $this->loadCSS();
        }

        // Load JavaScript for interactive elements
        $this->loadJS();

        // Process shortcodes
        $this->processShortcodes($row->text);
    }

    /**
     * Process all shortcodes in the content
     *
     * @param   string  &$text  The text to process
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function processShortcodes(&$text)
    {
        // Button shortcodes
        if ($this->params->get('enable_buttons', 1)) {
            $text = $this->processButtonShortcodes($text);
        }

        // Alert shortcodes
        if ($this->params->get('enable_alerts', 1)) {
            $text = $this->processAlertShortcodes($text);
        }

        // Gallery shortcodes
        if ($this->params->get('enable_gallery', 1)) {
            $text = $this->processGalleryShortcodes($text);
        }

        // Tabs shortcodes
        if ($this->params->get('enable_tabs', 1)) {
            $text = $this->processTabsShortcodes($text);
        }

        // Accordion shortcodes
        if ($this->params->get('enable_accordion', 1)) {
            $text = $this->processAccordionShortcodes($text);
        }

        // Countdown shortcodes
        if ($this->params->get('enable_countdown', 1)) {
            $text = $this->processCountdownShortcodes($text);
        }

        // Contact form shortcodes
        if ($this->params->get('enable_contact_form', 1)) {
            $text = $this->processContactFormShortcodes($text);
        }
    }

    /**
     * Process button shortcodes
     *
     * @param   string  $text  The text to process
     *
     * @return  string
     *
     * @since   1.0.0
     */
    private function processButtonShortcodes($text)
    {
        $pattern = '/\[button\s+([^\]]+)\]([^\[]*)\[\/button\]/i';
        
        return preg_replace_callback($pattern, function($matches) {
            $attributes = $this->parseAttributes($matches[1]);
            $content = trim($matches[2]);
            
            $url = $attributes['url'] ?? '#';
            $style = $attributes['style'] ?? 'primary';
            $size = $attributes['size'] ?? '';
            $target = $attributes['target'] ?? '_self';
            $class = $attributes['class'] ?? '';
            
            // Build button classes
            $buttonClass = 'btn btn-' . $style;
            if ($size) {
                $buttonClass .= ' btn-' . $size;
            }
            if ($class) {
                $buttonClass .= ' ' . $class;
            }
            
            return '<a href="' . htmlspecialchars($url) . '" class="' . $buttonClass . '" target="' . htmlspecialchars($target) . '">' . htmlspecialchars($content) . '</a>';
        }, $text);
    }

    /**
     * Process alert shortcodes
     *
     * @param   string  $text  The text to process
     *
     * @return  string
     *
     * @since   1.0.0
     */
    private function processAlertShortcodes($text)
    {
        $pattern = '/\[alert\s+([^\]]+)\]([^\[]*)\[\/alert\]/i';
        
        return preg_replace_callback($pattern, function($matches) {
            $attributes = $this->parseAttributes($matches[1]);
            $content = trim($matches[2]);
            
            $type = $attributes['type'] ?? 'info';
            $dismissible = isset($attributes['dismissible']) ? $attributes['dismissible'] : 'true';
            $class = $attributes['class'] ?? '';
            
            // Build alert classes
            $alertClass = 'alert alert-' . $type;
            if ($dismissible === 'true') {
                $alertClass .= ' alert-dismissible fade show';
            }
            if ($class) {
                $alertClass .= ' ' . $class;
            }
            
            $dismissButton = '';
            if ($dismissible === 'true') {
                $dismissButton = '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            }
            
            return '<div class="' . $alertClass . '" role="alert">' . $content . $dismissButton . '</div>';
        }, $text);
    }

    /**
     * Process gallery shortcodes
     *
     * @param   string  $text  The text to process
     *
     * @return  string
     *
     * @since   1.0.0
     */
    private function processGalleryShortcodes($text)
    {
        $pattern = '/\[gallery\s+([^\]]+)\]/i';
        
        return preg_replace_callback($pattern, function($matches) {
            $attributes = $this->parseAttributes($matches[1]);
            
            $images = $attributes['images'] ?? '';
            $layout = $attributes['layout'] ?? 'grid';
            $columns = $attributes['columns'] ?? '3';
            $class = $attributes['class'] ?? '';
            
            if (empty($images)) {
                return '<div class="alert alert-warning">Gallery shortcode: No images specified</div>';
            }
            
            $imageIds = explode(',', $images);
            $galleryId = 'gallery-' . uniqid();
            
            $galleryClass = 'content-shortcodes-gallery gallery-' . $layout;
            if ($class) {
                $galleryClass .= ' ' . $class;
            }
            
            $html = '<div id="' . $galleryId . '" class="' . $galleryClass . '" data-columns="' . $columns . '">';
            
            foreach ($imageIds as $imageId) {
                $imageId = trim($imageId);
                if ($imageId) {
                    $image = $this->getImageById($imageId);
                    if ($image) {
                        $html .= '<div class="gallery-item">';
                        $html .= '<img src="' . $image->url . '" alt="' . htmlspecialchars($image->alt) . '" class="img-fluid">';
                        if ($image->caption) {
                            $html .= '<div class="gallery-caption">' . htmlspecialchars($image->caption) . '</div>';
                        }
                        $html .= '</div>';
                    }
                }
            }
            
            $html .= '</div>';
            
            return $html;
        }, $text);
    }

    /**
     * Process tabs shortcodes
     *
     * @param   string  $text  The text to process
     *
     * @return  string
     *
     * @since   1.0.0
     */
    private function processTabsShortcodes($text)
    {
        $pattern = '/\[tabs\s+([^\]]*)\](.*?)\[\/tabs\]/is';
        
        return preg_replace_callback($pattern, function($matches) {
            $attributes = $this->parseAttributes($matches[1]);
            $content = $matches[2];
            
            $class = $attributes['class'] ?? '';
            $tabsId = 'tabs-' . uniqid();
            
            // Parse tab content
            $tabPattern = '/\[tab\s+title="([^"]+)"\](.*?)\[\/tab\]/is';
            preg_match_all($tabPattern, $content, $tabMatches, PREG_SET_ORDER);
            
            if (empty($tabMatches)) {
                return '<div class="alert alert-warning">Tabs shortcode: No valid tabs found</div>';
            }
            
            $tabsClass = 'content-shortcodes-tabs';
            if ($class) {
                $tabsClass .= ' ' . $class;
            }
            
            $html = '<div class="' . $tabsClass . '">';
            $html .= '<ul class="nav nav-tabs" id="' . $tabsId . '-nav" role="tablist">';
            
            $tabContent = '<div class="tab-content" id="' . $tabsId . '-content">';
            
            foreach ($tabMatches as $index => $tab) {
                $title = $tab[1];
                $tabContent_text = $tab[2];
                $tabId = $tabsId . '-tab-' . $index;
                $active = $index === 0 ? ' active' : '';
                
                $html .= '<li class="nav-item" role="presentation">';
                $html .= '<button class="nav-link' . $active . '" id="' . $tabId . '-tab" data-bs-toggle="tab" data-bs-target="#' . $tabId . '" type="button" role="tab">';
                $html .= htmlspecialchars($title);
                $html .= '</button></li>';
                
                $tabContent .= '<div class="tab-pane fade' . $active . ($index === 0 ? ' show' : '') . '" id="' . $tabId . '" role="tabpanel">';
                $tabContent .= $tabContent_text;
                $tabContent .= '</div>';
            }
            
            $html .= '</ul>';
            $html .= $tabContent;
            $html .= '</div></div>';
            
            return $html;
        }, $text);
    }

    /**
     * Process accordion shortcodes
     *
     * @param   string  $text  The text to process
     *
     * @return  string
     *
     * @since   1.0.0
     */
    private function processAccordionShortcodes($text)
    {
        $pattern = '/\[accordion\s+([^\]]*)\](.*?)\[\/accordion\]/is';
        
        return preg_replace_callback($pattern, function($matches) {
            $attributes = $this->parseAttributes($matches[1]);
            $content = $matches[2];
            
            $class = $attributes['class'] ?? '';
            $accordionId = 'accordion-' . uniqid();
            
            // Parse accordion items
            $itemPattern = '/\[item\s+title="([^"]+)"\](.*?)\[\/item\]/is';
            preg_match_all($itemPattern, $content, $itemMatches, PREG_SET_ORDER);
            
            if (empty($itemMatches)) {
                return '<div class="alert alert-warning">Accordion shortcode: No valid items found</div>';
            }
            
            $accordionClass = 'content-shortcodes-accordion accordion';
            if ($class) {
                $accordionClass .= ' ' . $class;
            }
            
            $html = '<div class="' . $accordionClass . '" id="' . $accordionId . '">';
            
            foreach ($itemMatches as $index => $item) {
                $title = $item[1];
                $itemContent = $item[2];
                $itemId = $accordionId . '-item-' . $index;
                $show = $index === 0 ? ' show' : '';
                
                $html .= '<div class="accordion-item">';
                $html .= '<h2 class="accordion-header" id="heading-' . $itemId . '">';
                $html .= '<button class="accordion-button' . ($index === 0 ? '' : ' collapsed') . '" type="button" data-bs-toggle="collapse" data-bs-target="#' . $itemId . '">';
                $html .= htmlspecialchars($title);
                $html .= '</button></h2>';
                $html .= '<div id="' . $itemId . '" class="accordion-collapse collapse' . $show . '" data-bs-parent="#' . $accordionId . '">';
                $html .= '<div class="accordion-body">';
                $html .= $itemContent;
                $html .= '</div></div></div>';
            }
            
            $html .= '</div>';
            
            return $html;
        }, $text);
    }

    /**
     * Process countdown shortcodes
     *
     * @param   string  $text  The text to process
     *
     * @return  string
     *
     * @since   1.0.0
     */
    private function processCountdownShortcodes($text)
    {
        $pattern = '/\[countdown\s+([^\]]+)\]/i';
        
        return preg_replace_callback($pattern, function($matches) {
            $attributes = $this->parseAttributes($matches[1]);
            
            $date = $attributes['date'] ?? '';
            $time = $attributes['time'] ?? '00:00:00';
            $format = $attributes['format'] ?? 'days,hours,minutes,seconds';
            $class = $attributes['class'] ?? '';
            $message = $attributes['message'] ?? 'Countdown finished!';
            
            if (empty($date)) {
                return '<div class="alert alert-warning">Countdown shortcode: No date specified</div>';
            }
            
            $countdownId = 'countdown-' . uniqid();
            $targetDate = $date . ' ' . $time;
            
            $countdownClass = 'content-shortcodes-countdown';
            if ($class) {
                $countdownClass .= ' ' . $class;
            }
            
            $html = '<div id="' . $countdownId . '" class="' . $countdownClass . '" data-target="' . $targetDate . '" data-format="' . $format . '" data-message="' . htmlspecialchars($message) . '">';
            $html .= '<div class="countdown-display">';
            $html .= '<div class="countdown-item"><span class="countdown-number" data-type="days">0</span><span class="countdown-label">Days</span></div>';
            $html .= '<div class="countdown-item"><span class="countdown-number" data-type="hours">0</span><span class="countdown-label">Hours</span></div>';
            $html .= '<div class="countdown-item"><span class="countdown-number" data-type="minutes">0</span><span class="countdown-label">Minutes</span></div>';
            $html .= '<div class="countdown-item"><span class="countdown-number" data-type="seconds">0</span><span class="countdown-label">Seconds</span></div>';
            $html .= '</div>';
            $html .= '<div class="countdown-message" style="display: none;">' . htmlspecialchars($message) . '</div>';
            $html .= '</div>';
            
            return $html;
        }, $text);
    }

    /**
     * Process contact form shortcodes
     *
     * @param   string  $text  The text to process
     *
     * @return  string
     *
     * @since   1.0.0
     */
    private function processContactFormShortcodes($text)
    {
        $pattern = '/\[contact_form\s+([^\]]*)\]/i';
        
        return preg_replace_callback($pattern, function($matches) {
            $attributes = $this->parseAttributes($matches[1]);
            
            $email = $attributes['email'] ?? '';
            $subject = $attributes['subject'] ?? 'Contact Form Submission';
            $class = $attributes['class'] ?? '';
            $redirect = $attributes['redirect'] ?? '';
            
            $formId = 'contact-form-' . uniqid();
            
            $formClass = 'content-shortcodes-contact-form';
            if ($class) {
                $formClass .= ' ' . $class;
            }
            
            $html = '<form id="' . $formId . '" class="' . $formClass . '" method="post" action="' . Uri::current() . '">';
            $html .= '<input type="hidden" name="task" value="contactform.submit">';
            $html .= '<input type="hidden" name="form_id" value="' . $formId . '">';
            $html .= '<input type="hidden" name="' . Session::getFormToken() . '" value="1">';
            if ($email) {
                $html .= '<input type="hidden" name="to_email" value="' . htmlspecialchars($email) . '">';
            }
            if ($subject) {
                $html .= '<input type="hidden" name="subject" value="' . htmlspecialchars($subject) . '">';
            }
            if ($redirect) {
                $html .= '<input type="hidden" name="redirect" value="' . htmlspecialchars($redirect) . '">';
            }
            
            $html .= '<div class="mb-3">';
            $html .= '<label for="' . $formId . '-name" class="form-label">' . Text::_('PLG_CONTENT_CONTENTSHORTCODES_NAME') . ' *</label>';
            $html .= '<input type="text" class="form-control" id="' . $formId . '-name" name="name" required>';
            $html .= '</div>';
            
            $html .= '<div class="mb-3">';
            $html .= '<label for="' . $formId . '-email" class="form-label">' . Text::_('PLG_CONTENT_CONTENTSHORTCODES_EMAIL') . ' *</label>';
            $html .= '<input type="email" class="form-control" id="' . $formId . '-email" name="email" required>';
            $html .= '</div>';
            
            $html .= '<div class="mb-3">';
            $html .= '<label for="' . $formId . '-message" class="form-label">' . Text::_('PLG_CONTENT_CONTENTSHORTCODES_MESSAGE') . ' *</label>';
            $html .= '<textarea class="form-control" id="' . $formId . '-message" name="message" rows="5" required></textarea>';
            $html .= '</div>';
            
            $html .= '<button type="submit" class="btn btn-primary">' . Text::_('PLG_CONTENT_CONTENTSHORTCODES_SEND_MESSAGE') . '</button>';
            $html .= '</form>';
            
            return $html;
        }, $text);
    }

    /**
     * Parse attributes from shortcode string
     *
     * @param   string  $attributeString  The attribute string to parse
     *
     * @return  array
     *
     * @since   1.0.0
     */
    private function parseAttributes($attributeString)
    {
        $attributes = array();
        
        if (empty($attributeString)) {
            return $attributes;
        }
        
        // Parse key="value" pairs
        preg_match_all('/(\w+)="([^"]*)"/', $attributeString, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $attributes[$match[1]] = $match[2];
        }
        
        return $attributes;
    }

    /**
     * Get image by ID
     *
     * @param   string  $imageId  The image ID
     *
     * @return  object|null
     *
     * @since   1.0.0
     */
    private function getImageById($imageId)
    {
        try {
            $query = $this->db->getQuery(true)
                ->select('*')
                ->from($this->db->quoteName('#__media'))
                ->where($this->db->quoteName('id') . ' = ' . (int) $imageId);
            
            $this->db->setQuery($query);
            $image = $this->db->loadObject();
            
            if ($image) {
                $image->url = Uri::root() . $image->path;
                $image->alt = $image->alt_text ?: $image->name;
                $image->caption = $image->caption ?: '';
            }
            
            return $image;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Handle contact form submissions
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function onAfterInitialise()
    {
        $input = Factory::getApplication()->input;
        
        if ($input->get('task') === 'contactform.submit') {
            $this->handleContactFormSubmission();
        }
    }

    /**
     * Process contact form submission
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function handleContactFormSubmission()
    {
        $app = Factory::getApplication();
        $input = $app->input;
        
        // Check CSRF token
        if (!Session::checkToken()) {
            $app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
            return;
        }
        
        // Get form data
        $name = $input->post->getString('name', '');
        $email = $input->post->getString('email', '');
        $message = $input->post->getString('message', '');
        $toEmail = $input->post->getString('to_email', '');
        $subject = $input->post->getString('subject', 'Contact Form Submission');
        $redirect = $input->post->getString('redirect', '');
        
        // Validate required fields
        if (empty($name) || empty($email) || empty($message) || empty($toEmail)) {
            $app->enqueueMessage(Text::_('PLG_CONTENT_CONTENTSHORTCODES_ERROR_FORM_SUBMISSION'), 'error');
            return;
        }
        
        // Validate emails
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $app->enqueueMessage(Text::_('PLG_CONTENT_CONTENTSHORTCODES_ERROR_INVALID_EMAIL'), 'error');
            return;
        }
        
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $app->enqueueMessage(Text::_('PLG_CONTENT_CONTENTSHORTCODES_ERROR_INVALID_RECIPIENT_EMAIL'), 'error');
            return;
        }
        
        // Send email
        try {
            $mailer = Factory::getMailer();
            $mailer->setSender(array($email, $name));
            $mailer->addRecipient($toEmail);
            $mailer->setSubject($subject);
            $mailer->setBody($message);
            $mailer->isHTML(false);
            
            if ($mailer->Send()) {
                $app->enqueueMessage(Text::_('PLG_CONTENT_CONTENTSHORTCODES_SUCCESS_FORM_SENT'), 'success');
                
                if (!empty($redirect)) {
                    $app->redirect($redirect);
                }
            } else {
                $app->enqueueMessage(Text::_('PLG_CONTENT_CONTENTSHORTCODES_ERROR_FORM_SUBMISSION'), 'error');
            }
        } catch (Exception $e) {
            $app->enqueueMessage(Text::_('PLG_CONTENT_CONTENTSHORTCODES_ERROR_FORM_SUBMISSION'), 'error');
        }
    }

    /**
     * Load CSS files
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function loadCSS()
    {
        $document = Factory::getDocument();
        $document->addStyleSheet(Uri::root() . 'plugins/content/contentshortcodes/css/shortcodes.css');
    }

    /**
     * Load JavaScript files
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function loadJS()
    {
        $document = Factory::getDocument();
        $document->addScript(Uri::root() . 'plugins/content/contentshortcodes/js/shortcodes.js');
    }
}
