<?php

$rcmail = rcmail::get_instance();
$user = $rcmail->user;;
require_once(dirname(__FILE__) . '/carddav_backend.php');

class carddav extends rcube_plugin
{
  public $task = 'addressbook|mail|settings';
  private $abook_id = "CardDAV";
  
  public function init()
  {{{
    $this->add_hook('addressbooks_list', array($this, 'address_sources'));
    $this->add_hook('addressbook_get', array($this, 'get_address_book'));

    $this->add_hook('preferences_list', array($this, 'cd_preferences'));
    $this->add_hook('preferences_save', array($this, 'cd_save'));
    $this->add_hook('preferences_sections_list',array($this, 'cd_preferences_section'));

    
    // use this address book for autocompletion queries
    // (maybe this should be configurable by the user?)
    $config = rcmail::get_instance()->config;
    $sources = (array) $config->get('autocomplete_addressbooks', array('sql'));
    if (!in_array($this->abook_id, $sources)) {
      $sources[] = $this->abook_id;
      $config->set('autocomplete_addressbooks', $sources);
    }
  }}}
  
  public function address_sources($p)
  {{{
    $abook = new carddav_backend;
    $rcmail = rcmail::get_instance();
    $prefs = $rcmail->config->get('carddav', array());
    if ($prefs['use_carddav'])
      $p['sources'][$this->abook_id] = array(
        'id' => $this->abook_id,
        'name' => 'CardDAV',
        'readonly' => $abook->readonly,
        'groups' => $abook->groups,
      );
    return $p;
  }}}
  
  public function get_address_book($p)
  {{{
    if ($p['id'] === $this->abook_id) {
      $p['instance'] = new carddav_backend;
    }
    
    return $p;
  }}}
 
  // user preferences
  function cd_preferences($args) {
    if($args['section'] == 'cd_preferences') {
      $this->add_texts('localization/', false);
      $rcmail = rcmail::get_instance();

      $prefs = $rcmail->config->get('carddav', array());
      
      // check box for username
      $use_carddav = $prefs['use_carddav'];
      $checkbox = new html_checkbox(array('name' => '_cd_use_carddav', 'value' => 1));
      $content_use_carddav = $checkbox->show($prefs['use_carddav']?1:0);
      // input box for username
      $input = new html_inputfield(array('name' => '_cd_username', 'type' => 'text', 'autocomplete' => 'off', 'value' => $prefs['username']));
      $content_username = $input->show();
      // input box for username
      $input = new html_inputfield(array('name' => '_cd_password', 'type' => 'text', 'autocomplete' => 'off', 'value' => $prefs['password']));
      $content_password = $input->show();
      // input box for URL
      $input = new html_inputfield(array('name' => '_cd_url', 'type' => 'text', 'autocomplete' => 'off', 'value' => $prefs['url'], 'size'=>'40'));
      $content_url = $input->show();
      
      $args['blocks']['cd_preferences'] =  array(
        'options' => array(
                           array('title'=> Q($this->gettext('cd_use_carddav')), 'content' => $content_use_carddav), 
                           array('title'=> Q($this->gettext('cd_username')), 'content' => $content_username), 
                           array('title'=> Q($this->gettext('cd_password')), 'content' => $content_password),
                           array('title'=> Q($this->gettext('cd_url')), 'content' => $content_url),
                          ),
        'name'    => Q($this->gettext('cd_title'))
        );

    } 

    return($args);
  }

  // add a section to the preferences tab
  function cd_preferences_section($args) {
    $this->add_texts('localization/', false);
    $args['list']['cd_preferences'] = array(
      'id'      => 'cd_preferences',
      'section' => Q($this->gettext('cd_title'))
      );
    return($args);
  }

  // save preferences
  function cd_save($args) {
    if($args['section'] != 'cd_preferences') return;

    $rcmail = rcmail::get_instance();

    $prefs = array('use_carddav' => isset($_POST['_cd_use_carddav']) ? 1 : 0,
                   'username' => get_input_value('_cd_username', RCUBE_INPUT_POST),
                   'password' => get_input_value('_cd_password', RCUBE_INPUT_POST),
                   'url' => get_input_value('_cd_url', RCUBE_INPUT_POST));
    $args['prefs']['carddav'] = $prefs;
    return($args);
  }


 
}