<?php
/**
 * @version    5.2.0
 * @package    AllVideos (plugin)
 * @author     JoomlaWorks - http://www.joomlaworks.net
 * @copyright  Copyright (c) 2006 - 2019 JoomlaWorks Ltd. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

class plgContentJw_allvideos extends JPlugin
{
    // JoomlaWorks reference parameters
    public $plg_name              = "jw_allvideos";
    public $plg_copyrights_start  = "\n\n<!-- JoomlaWorks \"AllVideos\" Plugin (v5.2.0) starts here -->\n";
    public $plg_copyrights_end    = "\n<!-- JoomlaWorks \"AllVideos\" Plugin (v5.2.0) ends here -->\n\n";

    public function __construct(&$subject, $params)
    {
        parent::__construct($subject, $params);
    }

    // Joomla 1.5
    public function onPrepareContent(&$row, &$params, $page = 0)
    {
        $this->renderAllVideos($row, $params, $page = 0);
    }

    // Joomla 2.5+
    public function onContentPrepare($context, &$row, &$params, $page = 0)
    {
        $this->renderAllVideos($row, $params, $page = 0);
    }

    // The main function
    public function renderAllVideos(&$row, &$params, $page = 0)
    {
        // API
        if (version_compare(JVERSION, '2.5.0', 'ge')) {
            jimport('joomla.html.parameter');
        }
        jimport('joomla.filesystem.file');
        $app = JFactory::getApplication();
        $document  = JFactory::getDocument();

        // Assign paths
        $sitePath = JPATH_SITE;
        $siteUrl  = JURI::root(true);
        if (version_compare(JVERSION, '2.5.0', 'ge')) {
            $pluginLivePath = $siteUrl.'/plugins/content/'.$this->plg_name.'/'.$this->plg_name;
        } else {
            $pluginLivePath = $siteUrl.'/plugins/content/'.$this->plg_name;
        }

        // Check if plugin is enabled
        if (JPluginHelper::isEnabled('content', $this->plg_name)==false) {
            return;
        }

        // Load the plugin language file the proper way
        JPlugin::loadLanguage('plg_content_'.$this->plg_name, JPATH_ADMINISTRATOR);

        // Includes
        $tagReplace = array();
        require_once dirname(__FILE__).'/'.$this->plg_name.'/includes/helper.php';
        require dirname(__FILE__).'/'.$this->plg_name.'/includes/sources.php';

        // Simple performance check to determine whether plugin should process further
        $grabTags = strtolower(implode("|", array_keys($tagReplace)));
        if (preg_match("~{(".$grabTags.")}~is", $row->text)==false) {
            return;
        }



        // ----------------------------------- Get plugin parameters -----------------------------------

        // Get plugin info
        $plugin = JPluginHelper::getPlugin('content', $this->plg_name);

        // Control external parameters and set variable for controlling plugin layout within modules
        if (!$params) {
            $params = class_exists('JParameter') ? new JParameter(null) : new JRegistry(null);
        }
        if (is_string($params)) {
            $params = class_exists('JParameter') ? new JParameter($params) : new JRegistry($params);
        }
        $parsedInModule = $params->get('parsedInModule');

        $pluginParams = class_exists('JParameter') ? new JParameter($plugin->params) : new JRegistry($plugin->params);

        $playerTemplate         = ($params->get('playerTemplate')) ? $params->get('playerTemplate') : $pluginParams->get('playerTemplate', 'Responsive');
        /* Video Parameters */
        $vfolder                = ($params->get('vfolder')) ? $params->get('vfolder') : $pluginParams->get('vfolder', 'images/stories/videos');
        $vwidth                 = ($params->get('vwidth')) ? $params->get('vwidth') : $pluginParams->get('vwidth', 400);
        $vheight                = ($params->get('vheight')) ? $params->get('vheight') : $pluginParams->get('vheight', 300);
        $allowVideoDownloading  = $pluginParams->get('allowVideoDownloading', 0);
        /* Audio Parameters */
        $afolder                = ($params->get('afolder')) ? $params->get('afolder') : $pluginParams->get('afolder', 'images/stories/audio');
        $awidth                 = ($params->get('awidth')) ? $params->get('awidth') : $pluginParams->get('awidth', 480);
        $aheight                = ($params->get('aheight')) ? $params->get('aheight') : $pluginParams->get('aheight', 24);
        $allowAudioDownloading  = $pluginParams->get('allowAudioDownloading', 0);
        /* Global Parameters */
        $maxwidth               = trim($pluginParams->get('maxwidth', ''));
        $maxwidth               = ($maxwidth) ? ' style="max-width:'.$maxwidth.';"' : '';
        $controls               = $pluginParams->get('controls', '1');
        $controls               = ($controls) ? ' controls' : '';
        $autoplay               = ($params->get('autoplay')) ? $params->get('autoplay') : $pluginParams->get('autoplay', 0);
        $loop                   = ($params->get('loop')) ? $params->get('loop') : $pluginParams->get('loop', 0);
        $ytnocookie             = ($params->get('ytnocookie')) ? $params->get('ytnocookie') : $pluginParams->get('ytnocookie', 0);

        // Variable cleanups for K2
        if (JRequest::getCmd('format')=='raw') {
            $this->plg_copyrights_start = '';
            $this->plg_copyrights_end = '';
        }

        // Assign the AllVideos helper class
        $AllVideosHelper = new AllVideosHelper;



        // ----------------------------------- Render the output -----------------------------------

        // Append head includes only when the document is in HTML mode
        if (JRequest::getCmd('format')=='html' || JRequest::getCmd('format')=='') {
            // CSS
            $avCSS = $AllVideosHelper->getTemplatePath($this->plg_name, 'css/template.css', $playerTemplate);
            $avCSS = $avCSS->http;
            $document->addStyleSheet($avCSS.'?v=5.2.0');

            // JS
            $document->addScript($pluginLivePath.'/includes/js/behaviour.js?v=5.2.0');
        }

        // Loop throught the found tags
        $tagReplace = array_change_key_case($tagReplace, CASE_LOWER);
        foreach ($tagReplace as $plg_tag => $value) {
            $cloned_plg_tag = $plg_tag;
            $plg_tag = strtolower($plg_tag);

            // expression to search for
            $regex = "~{".$plg_tag."}.*?{/".$plg_tag."}~is";

            // replacements for content to avoid issues with RegEx
            $row->text = str_replace('~', '&#126;', $row->text);

            // process tags
            if (preg_match_all($regex, $row->text, $matches, PREG_PATTERN_ORDER)) {

                // start the replace loop
                foreach ($matches[0] as $key => $match) {
                    $tagcontent = preg_replace("/{.+?}/", "", $match);
                    $tagcontent = str_replace(array('"','\'','`'), array('&quot;','&apos;','&#x60;'), $tagcontent); // Address potential XSS attacks
                    $tagparams = explode('|', $tagcontent);
                    $tagsource = trim(strip_tags($tagparams[0]));

                    // Prepare the HTML
                    $output = new JObject;

                    $output->controls = $controls;

                    // Width/height/source folder split per media type
                    if (in_array($plg_tag, array(
                        'flac',
                        'flacremote',
                        'm4a',
                        'm4aremote',
                        'mp3',
                        'mp3remote',
                        'oga',
                        'ogaremote',
                        'ogg',
                        'oggremote',
                        'wav',
                        'wavremote',
                        'soundcloud'
                    ))) {
                        if ($plg_tag=='soundcloud') {
                            if (strpos($tagsource, '/sets/') !== false) {
                                $output->mediaTypeClass = ' avSoundCloudSet';
                            } else {
                                $output->mediaTypeClass = ' avSoundCloudSong';
                            }
                            $output->mediaType = 'provider';
                            $output->source = $tagsource;
                            $output->posterFrame = '';
                        } else {
                            $output->mediaTypeClass = ' avAudio';
                            $output->mediaType = 'audio';
                            if (strpos($plg_tag, 'remote') !== false) {
                                $output->source = $tagsource;
                                $output->posterFrame = ($plg_tag == 'flacremote') ? substr($tagsource, 0, -4).'jpg' : substr($tagsource, 0, -3).'jpg';
                                $output->posterFrame = "background-image:url('".$output->posterFrame."');";
                            } else {
                                $output->source = "$siteUrl/$afolder/$tagsource.$plg_tag";
                                $posterFramePath = $sitePath.'/'.$afolder;
                                if (JFile::exists($posterFramePath.'/'.$tagsource.'.jpg')) {
                                    $output->posterFrame = $siteUrl.'/'.$afolder.'/'.$tagsource.'.jpg';
                                } elseif (JFile::exists($posterFramePath.'/'.$tagsource.'.png')) {
                                    $output->posterFrame = $siteUrl.'/'.$afolder.'/'.$tagsource.'.png';
                                } elseif (JFile::exists($posterFramePath.'/'.$tagsource.'.gif')) {
                                    $output->posterFrame = $siteUrl.'/'.$afolder.'/'.$tagsource.'.gif';
                                } elseif (JFile::exists($posterFramePath.'/'.$tagsource.'.webp')) {
                                    $output->posterFrame = $siteUrl.'/'.$afolder.'/'.$tagsource.'.webp';
                                } else {
                                    $output->posterFrame = '';
                                    $output->mediaTypeClass .= ' avNoPoster';
                                }
                                if ($output->posterFrame) {
                                    $output->posterFrame = "background-image:url('".$output->posterFrame."');";
                                    $aheight = ($awidth * 9 / 16);
                                }
                            }
                        }

                        $final_awidth = (@$tagparams[1]) ? $tagparams[1] : $awidth;
                        $final_aheight = (@$tagparams[2]) ? $tagparams[2] : $aheight;

                        $output->playerWidth = $final_awidth;
                        $output->playerHeight = $final_aheight;
                        $output->folder = $afolder;

                        if (!$allowAudioDownloading && $controls) {
                            $output->controls = $controls.' controlsList="nodownload"';
                        }
                    } else {
                        if (in_array($plg_tag, array('dailymotion','facebook','flickr','twitch','vimeo','youtube'))) {
                            $output->mediaTypeClass = ' avVideo';
                            $output->mediaType = 'provider';
                            $output->source = $tagsource;
                            $output->posterFrame = '';
                        } else {
                            $output->mediaTypeClass = ' avVideo';
                            $output->mediaType = 'video';
                            if (strpos($plg_tag, 'remote') !== false) {
                                $output->source = $tagsource;
                                $output->posterFrame = '';
                            } else {
                                $output->source = "$siteUrl/$vfolder/$tagsource.$plg_tag";
                                $posterFramePath = $sitePath.'/'.$vfolder;
                                if (JFile::exists($posterFramePath.'/'.$tagsource.'.jpg')) {
                                    $output->posterFrame = $siteUrl.'/'.$vfolder.'/'.$tagsource.'.jpg';
                                } elseif (JFile::exists($posterFramePath.'/'.$tagsource.'.png')) {
                                    $output->posterFrame = $siteUrl.'/'.$vfolder.'/'.$tagsource.'.png';
                                } elseif (JFile::exists($posterFramePath.'/'.$tagsource.'.gif')) {
                                    $output->posterFrame = $siteUrl.'/'.$vfolder.'/'.$tagsource.'.gif';
                                } elseif (JFile::exists($posterFramePath.'/'.$tagsource.'.webp')) {
                                    $output->posterFrame = $siteUrl.'/'.$vfolder.'/'.$tagsource.'.webp';
                                } else {
                                    $output->posterFrame = '';
                                }

                                if ($output->posterFrame) {
                                    $output->posterFrame = ' poster="'.$output->posterFrame.'"';
                                }
                            }
                        }

                        $final_vwidth = (@$tagparams[1]) ? $tagparams[1] : $vwidth;
                        $final_vheight = (@$tagparams[2]) ? $tagparams[2] : $vheight;

                        $output->playerWidth = $final_vwidth;
                        $output->playerHeight = $final_vheight;
                        $output->folder = $vfolder;

                        if (!$allowVideoDownloading && $controls) {
                            $output->controls = $controls.' controlsList="nodownload"';
                        }
                    }

                    // Autoplay
                    $tag_autoplay = (@$tagparams[3]) ? $tagparams[3] : $autoplay;
                    $provider_autoplay = ($tag_autoplay) ? 'true' : 'false';
                    $player_autoplay = ($tag_autoplay) ? ' autoplay' : '';

                    // Loop
                    $final_loop = (@$tagparams[4]) ? $tagparams[4] : $loop;
                    $final_loop = ($final_loop) ? ' loop' : '';

                    // Special treatment for specific video providers
                    if ($plg_tag=="dailymotion") {
                        $tagsource = preg_replace("~(http|https):(.+?)dailymotion.com\/video\/~s", "", $tagsource);
                        $tagsourceDailymotion = explode('_', $tagsource);
                        $tagsource = $tagsourceDailymotion[0];
                        if ($provider_autoplay=='true') {
                            if (strpos($tagsource, '?')!==false) {
                                $tagsource = $tagsource.'&amp;autoplay=1';
                            } else {
                                $tagsource = $tagsource.'?autoplay=1';
                            }
                        }
                    }

                    if ($plg_tag=="facebook") {
                        $tagsource = urlencode($tagsource);
                    }

                    if ($plg_tag=="flickr") {
                        if (strpos($tagsource, 'http')!==false) {
                            $tagsource = urlencode($tagsource);
                        }
                    }

                    if ($plg_tag=="twitch") {
                        if (strpos($tagsource, 'http')!==false) {
                            $tagsource = preg_replace("~(http|https):(.+?)twitch.tv\/videos\/~s", "", $tagsource);
                        }
                    }

                    if ($plg_tag=="vimeo") {
                        $tagsource = preg_replace("~(http|https):(.+?)vimeo.com\/~s", "", $tagsource);
                        if (strpos($tagsource, '?')!==false) {
                            $tagsource = $tagsource.'&amp;portrait=0';
                        } else {
                            $tagsource = $tagsource.'?portrait=0';
                        }
                        if ($provider_autoplay=='true') {
                            $tagsource = $tagsource.'&amp;autoplay=1';
                        }
                        if ($final_loop) {
                            $tagsource = $tagsource.'&amp;loop=1';
                        }
                    }

                    if ($plg_tag=="youtube") {
                        // Check the presence of fully pasted URLs
                        if (strpos($tagsource, 'youtube.com')!==false) {
                            $ytQuery = parse_url($tagsource, PHP_URL_QUERY);
                            if (is_array($ytQuery)) {
                                $ytQuery = implode("&", $ytQuery);
                            }
                            $ytQuery = str_replace('&amp;', '&', $ytQuery);
                        } elseif (strpos($tagsource, 'youtu.be')!==false) {
                            $ytQuery = explode('youtu.be/', $tagsource);
                            $ytQuery = $ytQuery[1];
                            $tagsource = $ytQuery;
                        } else {
                            $ytQuery = $tagsource;
                        }

                        // Process string
                        if (strpos($ytQuery, '&')!==false) {
                            $ytQuery = explode('&', $ytQuery);
                            $ytParams = array();
                            foreach ($ytQuery as $ytParam) {
                                $ytParam = explode('=', $ytParam);
                                $ytParams[$ytParam[0]] = $ytParam[1];
                            }
                            if (array_key_exists('v', $ytParams)) {
                                $tagsource = $ytParams['v'];
                            } elseif (array_key_exists('list', $ytParams)) {
                                $tagsource = 'videoseries?list='.$ytParams['list'];
                            }
                        } elseif (strpos($ytQuery, '=')!==false) {
                            $ytQuery = explode('=', $ytQuery);
                            $ytParams = array();
                            $ytParams[$ytQuery[0]] = $ytQuery[1];
                            if (array_key_exists('v', $ytParams)) {
                                $tagsource = $ytParams['v'];
                            } elseif (array_key_exists('list', $ytParams)) {
                                $tagsource = 'videoseries?list='.$ytParams['list'];
                            }
                        } else {
                            if (substr($tagsource, 0, 2)=="PL") {
                                $tagsource = 'videoseries?list='.$tagsource;
                            }
                        }

                        if (strpos($tagsource, '?')!==false) {
                            $tagsource = $tagsource.'&amp;rel=0&amp;fs=1&amp;wmode=transparent';
                        } else {
                            $tagsource = $tagsource.'?rel=0&amp;fs=1&amp;wmode=transparent';
                        }

                        // Additional playback parameters
                        if ($provider_autoplay=='true') {
                            $tagsource = $tagsource.'&amp;autoplay=1';
                        }
                        if ($final_loop) {
                            $tagsource = $tagsource.'&amp;loop=1';
                        }
                    }

                    // Set a unique ID
                    $output->playerID = 'AVPlayerID_'.$key.'_'.md5($tagsource);

                    // Placeholder elements
                    $findAVparams = array(
                        "{SOURCE}",
                        "{SOURCEID}",
                        "{FOLDER}",
                        "{WIDTH}",
                        "{HEIGHT}",
                        "{PROVIDER_AUTOPLAY}",
                        "{PLAYER_LOOP}",
                        "{PLAYER_CONTROLS}",
                        "{PLAYER_AUTOPLAY}",
                        "{SITEURL}",
                        "{SITEURL_ABS}",
                        "{FILE_EXT}",
                        "{FILE_TYPE}",
                        "{PLUGIN_PATH}",
                        "{PLAYER_POSTER_FRAME}"
                    );

                    // Replacement elements
                    $replaceAVparams = array(
                        $tagsource,
                        $output->playerID,
                        $output->folder,
                        $output->playerWidth,
                        $output->playerHeight,
                        $provider_autoplay,
                        $final_loop,
                        $output->controls,
                        $player_autoplay,
                        $siteUrl,
                        substr(JURI::root(false), 0, -1),
                        $plg_tag,
                        str_replace("remote", "", $plg_tag),
                        $pluginLivePath,
                        $output->posterFrame
                    );

                    // Do the element replace
                    $output->player = str_replace($findAVparams, $replaceAVparams, $tagReplace[$cloned_plg_tag]);

                    // Post processing
                    // For YouTube
                    if ($ytnocookie) {
                        $output->player = str_replace('www.youtube.com/embed', 'www.youtube-nocookie.com/embed', $output->player);
                    }

                    // Fetch the template
                    ob_start();
                    $getTemplatePath = $AllVideosHelper->getTemplatePath($this->plg_name, 'default.php', $playerTemplate);
                    $getTemplatePath = $getTemplatePath->file;
                    include($getTemplatePath);
                    $getTemplate = $this->plg_copyrights_start.ob_get_contents().$this->plg_copyrights_end;
                    ob_end_clean();

                    // Output
                    $row->text = preg_replace("~{".$plg_tag."}".preg_quote($tagcontent)."{/".$plg_tag."}~is", $getTemplate, $row->text);
                } // End second foreach
            } // End if
        } // End first foreach
    } // End function
} // End class
