<?php
require_once dirname(__FILE__) . '/../tools.lib.php';

//! Demonstation of a smart menu
class SmartMenu
{
    //! The css class of the menu
    public $cssclass;

    //! Entries stored in menu
    public $entries = array();

    //! construct a smart menu
    public function __construct($cssclass = 'menu')
    {
        $this->cssclass = $cssclass;
    }

    //! Add a new entry in menu
    /**
    *
    * @param $display The display of the menu button
    * @param $link The link of the menu
    * @param $select_mode
    * - 'prefix' Check if current URI has prefix the link of entry
    * - 'equal' Current uri must be exactly the same as the link
    * - FALSE Dont check for uri
    * @param $extra_attr
    */
    public function add_link($display, $link, $select_mode = 'prefix', $extra_attr = array())
    {
        $this->entries[] = array(
			'type' => 'link',
			'display' => $display,
			'link' => $link,
			'select_mode' => $select_mode,
			'extra_attr' => $extra_attr
        );
    }

    //! Add custom entry in menu
    /**
    *
    * @param $display The display of the mneu
    * @param $extra_attr
    */
    public function add_entry($html, $extra_attr = array())
    {
        $this->entries[] = array(
			'type' => 'custom',
			'html' => $html,
			'select_mode' => FALSE,
			'extra_attr' => $extra_attr
        );
    }

    //! Render menu in output
    public function render()
    {	$ul = tag('ul')->push_parent();
    $ul->add_class($this->cssclass);
    $REQUEST_URL = (isset($_SERVER['PATH_INFO'])?$_SERVER['PATH_INFO']:$_SERVER['REQUEST_URI']);

    foreach($this->entries as $entry)
    {
        if ($entry['type'] == 'link')
        {
            $li = etag('li', tag('a', array('href' => url($entry['link'])), $entry['display']), $entry['extra_attr']);
            if ($entry['select_mode'] !== FALSE)
            {
                if ($entry['select_mode'] === 'prefix')
                {
                    if( $entry['link'] === substr($REQUEST_URL, 0, strlen($entry['link'])))
                    $li->add_class('selected');
                }
                else if ($entry['select_mode'] === 'equal')
                if( $entry['link'] === $REQUEST_URL)
                $li->add_class('selected');
            }
        }
        else if ($entry['type'] == 'custom')
        $li = etag('li html_escape_off', $entry['html'], $entry['extra_attr']);
    }
    return Output_HTMLTag::pop_parent();
    }
}
