<?php
/**
 * jBackend helloworld plugin for Joomla
 *
 * @author selfget.com (info@selfget.com)
 * @package jBackend
 * @copyright Copyright 2014 - 2015
 * @license GNU Public License
 * @link http://www.selfget.com
 * @version 0.9.1
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Plugin\CMSPlugin;

class plgJBackendKomento extends JPlugin
{
  /**
   * Application object
   *
   * @var    CMSApplication
   * @since  0.9.0
   */
  protected $app;

  /**
   * Database object
   *
   * @var    DatabaseDriver
   * @since  0.9.0
   */
  protected $db;

  /**
   * Affects constructor behavior. If true, language files will be loaded automatically.
   *
   * @var    boolean
   * @since  0.9.0
   */
  protected $autoloadLanguage = true;

  /**
   * This function is called on initialization.
   *
   * @return  void.
   *
   * @since   0.9.0
   */
  public function __construct(& $subject, $config)
  {
    parent::__construct($subject, $config);

    $params = new JRegistry($config['params']);

    $this->p = array();

    // Get plugin parameters
    $this->p['sort'] = $params->get('sort', 'oldest');
    $this->p['filter_state'] = $params->get('filter_state', 1);
    $this->p['max_limit'] = $params->get('max_limit', 20);

  }

  /**
   * Generate plugin specific errors
   *
   * @param  string  $errorCode  The error code to generate
   *
   * @return  array  The response of type error to return
   *
   * @since   0.9.0
   */
  public static function generateError($errorCode)
  {
    $error = array();
    $error['status'] = 'ko';

    switch($errorCode) {
      case 'REQ_ANS':
        $error['error_code'] = 'REQ_ANS';
        $error['error_description'] = 'Action not specified';
        break;
      case 'KOM_CNF':
        $error['error_code'] = 'KOM_CNF';
        $error['error_description'] = 'Comment not found';
        break;
      case 'KOM_ID':
        $error['error_code'] = 'KOM_ID';
        $error['error_description'] = 'Comment id not specified';
        break;
      case 'KOM_CNS':
        $error['error_code'] = 'KOM_CNS';
        $error['error_description'] = 'Component not specified';
        break;
      case 'KOM_CID':
        $error['error_code'] = 'KOM_CID';
        $error['error_description'] = 'Component item id (cid) not specified';
        break;
      case 'KOM_NON':
        $error['error_code'] = 'KOM_NON';
        $error['error_description'] = 'Komento not enabled on selected component';
        break;
      case 'KOM_DNY':
        $error['error_code'] = 'KOM_DNY';
        $error['error_description'] = 'User does not have the permission to post';
        break;
      case 'KOM_ENV':
        $error['error_code'] = 'KOM_ENV';
        $error['error_description'] = 'User email address is not valid or is not recognised';
        break;
    }

    return $error;
  }

  /**
   * Get all comment from cid
   *
   * @param   object    $response    The response generated
   * @param   object    $status      The boundary conditions (e.g. authentication status). Useful to return additional info
   *
   * @return  boolean   true if there are no problems (status = ok), false in case of errors (status = ko)
   *
   * @since   0.9.0
   */
  public function actionComments(&$response, &$status = null)
  {
    // Get request parameters
    $component = $this->app->input->getString('component', 'all');
    $cid = $this->app->input->getInt('cid', 'all');

    $options = array();
    $options['sort'] = $this->p['sort'];
    $options['parent_id'] = $this->app->input->getInt('parent_id', 'all');
    $options['sticked'] = $this->app->input->getInt('sticked', 'all');
    $options['limit'] = $this->app->input->getInt('limit', $this->p['max_limit']);
    $options['limitstart'] = $this->app->input->getInt('offset', 0);

    // Get plugin parameter
    $options['published'] = $this->p['filter_state'];
    $options['sort'] = $this->p['sort'];
    $options['random'] = $this->p['sort'] == 'random' ? 1 : 0;
    
    // Adjust request parameters
    if ( $options['limit'] > $this->p['max_limit'] ) $options['limit'] = $this->p['max_limit'];

    // Check if komento component is set
    if ( !isset( $component ) )
    {
      $response = self::generateError( 'KOM_CNS' ); // Component not specified
      return false;
    }

    // Get comments
    $all = $this->comments( $component, $cid, $options );
    $total = $this->count( $component, $cid, $options );

    $comments = array();
    foreach ($all as $comment) {
      // Get the accessible non-static properties
      $data = get_object_vars ( $comment );

      // Unset some data
      if ( isset($data['_errors']) ) unset($data['_errors']);

      array_push($comments, $data);
    }

    // Build response
    $response['status'] = 'ok';
    $response['total'] = $total;
    $response['limit'] = $options['limit'];
    $response['offset'] = $options['limitstart'];
    $response['pages_current'] = ceil( $options['limitstart'] / $options['limit'] ) + 1;
    $response['pages_total'] = ceil( $total / $options['limit'] );
    $response['comments'] = $comments;

    return true;
  }

  /**
   * Get one comment by id
   *
   * @param   object    $response    The response generated
   * @param   object    $status      The boundary conditions (e.g. authentication status). Useful to return additional info
   *
   * @return  boolean   true if there are no problems (status = ok), false in case of errors (status = ko)
   *
   * @since   0.9.0
   */
  public function actionComment(&$response, &$status = null)
  {
    // Get request parameters
    $id = $this->app->input->getInt('id');

    // Check if komento id is set
    if (!isset($id))
    {
      $response = self::generateError('KOM_ID'); // comment id not specified
      return false;
    }

    // Get one comment
    $comment = $this->comment( $id );

    // Check result
    if (empty($comment))
    {
      $response = self::generateError('KOM_CNF'); // Comment not found
      return false;
    }

    // Build response
    $response['status'] = 'ok';
    $response = array_merge($response, $comment);

    return true;
  }
  
  /**
   * Create a new comment
   *
   * @param   object    $response    The response generated
   * @param   object    $status      The boundary conditions (e.g. authentication status). Useful to return additional info
   *
   * @return  boolean   true if there are no problems (status = ok), false in case of errors (status = ko)
   *
   * @since   0.9.1
   */
  public function actionCreate(&$response, &$status = null)
  { 
    // construct comment data array with passed data	
	
	$comment_data = array(
						'component' => $this->app->input->getCmd('component', ''),
						'cid' => $this->app->input->getInt('id', 0),
						'title' => $this->app->input->getString('title', ''),
						'comment' => $this->app->input->getString('comment', ''),
						'name' => $this->app->input->getString('name', ''),
						'email' => $this->app->input->getString('email', ''),
						'url' => $this->app->input->getString('url', ''),
						'created_by' => $this->app->input->getInt('created_by', 0),
						'parent_id' => $this->app->input->getInt('parent_id', 0),
						'sticked' => $this->app->input->getInt('sticked', 0),
						'subscribe' => $this->app->input->getInt('subscribe', 0)
    		  );
    
    // construct comment data with demo data (for testing purpose only)
    /*
    $comment_data = array(
            'component' => 'com_content',
            'cid' => 2,
            'title' => 'Test title',
            'comment' => 'Test comment',
            'name' => 'Myname',
            'email' => 'email@email.com',
            'url' => 'https://www.google.com',
            'created_by' => 196,
            'parent_id' => 0,
            'sticked' => 0,
            'subscribe' => 1
            );
  */

  // check mandatory fields
  $component_system_name = $comment_data['component'];
  if (empty($component_system_name))
  {
    $response = self::generateError('KOM_CNS'); // component required
    return false;
  }

  $component_id = $comment_data['cid'];
  if (empty($component_id))
  {
    $response = self::generateError('KOM_CID'); // component item id required
    return false;
  }
    
  // check if the needed component is enabled and if moderation of comments is enabled
  $db = JFactory::getDbo();
	$query = $db->getQuery(true);
	$query->select($db->quoteName('params'));
	$query->from($db->quoteName('#__komento_configs'));
	$query->where($db->quoteName('component') . ' = '.  $db->quote($component_system_name));
	$db->setQuery($query);
	$komento_configs = json_decode($db->loadResult());
	$komento_enabled = (int)$komento_configs->enable_komento;
	if (!$komento_enabled == 1) {
    $response = self::generateError('KOM_NON'); // component required
    return false;
	}
	$moderation_enabled = (int)$komento_configs->enable_moderation;
	if (!$moderation_enabled == 1) {
		$comment_data['published'] = 1;
	} else {
		$comment_data['published'] = 0;
	}
	
	// check if the current user has the permission to post
	$user_id = (int)$comment_data['created_by'];
	$is_guest = !$user_id ? 1 : 0;
	$is_allowed = false;
	if (!$is_guest) {
		$user_groups = JAccess::getGroupsByUser($user_id);
	} else {
		$user_groups = JAccess::getGroupsByUser(0);
	}
	foreach ($user_groups as $group) {
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('rules'));
		$query->from($db->quoteName('#__komento_acl'));
		$query->where($db->quoteName('component') . ' = '.  $db->quote($component_system_name));
		$query->where($db->quoteName('cid') . ' = '.  $group);
		$db->setQuery($query);
		$komento_acl = json_decode($db->loadResult());
		$can_add = $komento_acl->add_comment;
		if ($can_add) {
			$is_allowed = true;
		}
	}
	if (!$is_allowed === true) {
    $response = self::generateError('KOM_DNY'); // current user does not have the permission to post
    return false;
	}
	
	// check if user email address is valid before subscribing
	$subscribe = $comment_data['subscribe'];
	$email = $comment_data['email'];
	if ($subscribe == 1 && !preg_match('^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$^', $email)) {
    $response = self::generateError('KOM_ENV'); // // user email address is not valid or is not recognised
    return false;
	}
    
    // function to write the new comment into the database
    function write($data)
	{	
		// enter the new comment into the database
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$columns = array(
						'component',
						'cid',
						'title',
						'comment',
						'name',
						'email',
						'url',
						'created_by',
						'parent_id',
						'sticked',
						'published'
						);
		$values = array(
						$db->quote($data['component']),
						$data['cid'],
						$db->quote($data['title']),
						$db->quote($data['comment']),
						$db->quote($data['name']),
						$db->quote($data['email']),
						$db->quote($data['url']),
						$data['created_by'],
						$data['parent_id'],
						$data['sticked'],
						$data['published']
						);
		$query
			->insert($db->quoteName('#__komento_comments'))
			->columns($db->quoteName($columns))
			->values(implode(',', $values));
		$db->setQuery($query);
		$db->execute();
		
		// get the id of the entered comment
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('LAST_INSERT_ID()');
		$db->setQuery($query);
		$comment_id = $db->loadResult();
		
		// enter the subscription into the database
		if ($data['subscribe'] == 1) {
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$columns = array(
							'type',
							'component',
							'cid',
							'userid',
							'fullname',
							'email',
							'created',
							'published'
							);
			$values = array(
							$db->quote('comment'),
							$db->quote($data['component']),
							$data['cid'],
							$data['created_by'],
							$db->quote($data['name']),
							$db->quote($data['email']),
							$db->quote(date("Y-m-d H:i:s")),
							1
							);
			$query
				->insert($db->quoteName('#__komento_subscription'))
				->columns($db->quoteName($columns))
				->values(implode(',', $values));
			$db->setQuery($query);
			$db->execute();
		}
		
		return $comment_id;
	}
    
    // write the new comment into the database
    $comment_id = write($comment_data);
    
    // build response
    if (!$comment_id) {
    	$response['status'] = 'ko';
    } else {
    	$response['status'] = 'ok';
		$response['id'] = $comment_id;
    }
    
    return true;
  }

  /**
   * Function to count all comments by filter
   *
   * @param   string    $component    The component (e.g. "com_content")
   * @param   string    $cid          The component item (e.g. article-id)
   * @param   array     $options      Filter options
   *
   * @return  int   count of comments
   *
   * @since   0.9.0
   *
   */
  public function count($component = 'all', $cid = 'all', $options = array())
  {
    JLoader::import('comments', JPATH_ROOT.'/components/com_komento/models');
    $model = JModelLegacy::getInstance('Comments', 'KomentoModel');

    $result = $model->getCount( $component, $cid, $options );

    return $result;
  }

  /**
   * Function to get all comments by filter
   *
   * @param   string    $component    The component (e.g. "com_content")
   * @param   string    $cid          The component item (e.g. article-id)
   * @param   array     $options      Filter options
   *
   * @return  array   array of comments
   *
   * @since   0.9.0
   *
   */
  public function comments($component = 'all', $cid = 'all', $options = array())
  {
    JLoader::import('comments', JPATH_ROOT.'/components/com_komento/models');
    $model = JModelLegacy::getInstance('Comments', 'KomentoModel');

    $result = $model->getComments( $component, $cid, $options );

    return $result;
  }

  /**
   * Function to get a single comment
   *
   * @param   int     $id      Id of the comment
   *
   * @return  array   Array of comment data
   *
   * @since   0.9.0
   *
   */
  public function comment($id)
  {
    // Create a new query object.
    $query = $this->db->getQuery(true);

    // Select record
    $query->select('*');
    $query->from( $this->db->quoteName( '#__komento_comments' ) );
    $query->where( $this->db->quoteName( 'id' ) . ' = '.$id );

    // Filter on plugin parameters
    if ( $this->p['filter_state'] != 'false' ) {
      $query->where( $this->db->quoteName( 'published' ) . ' = '. $this->p['filter_state'] );
    }

    $this->db->setQuery( $query );

    $result = $this->db->loadAssoc();

    return $result;
  }

  /**
   * Triggered before to check the module to call
   * It can be used to manipulate request variables, as example,
   * to set needed request variables when the client can't do this
   *
   */
  public function onBeforeCheckModule()
  {

  }

  /**
   * Fulfills requests for helloworld module
   *
   * @param   object    $module      The module invoked (this is the same of onRequest<Module>)
   * @param   object    $response    The response generated
   * @param   object    $status      The boundary conditions (e.g. authentication status). Useful to return additional info
   *
   * @return  boolean   true if there are no problems (status = ok), false in case of errors (status = ko)
   */
  public function onRequestKomento($module, &$response, &$status = null)
  {
    if ($module !== 'komento') return true; // Check if this is the triggered module or exit

    // Add to module call stack
    jBackendHelper::moduleStack($status, 'komento'); // Add this request to the module stack register

    // Now check the request
    // Each request must have three params, action/module/resource
    // action: one of the RESTful actions (e.g. GET, POST, ...)
    // module: is the name of the module to call (jBackend plugin) (e.g. helloworld)
    // resource: is the resource requested to the module (e.g. an article for the content module)
    $action = $this->app->input->getString('action');
    $resource = $this->app->input->getString('resource');
    // module already checked by jBackend before to dispatch the request,
    // so no needs to check the module if we are here :)

    // Check if the action is specified
    if (is_null($action)) {
      $response = self::generateError('REQ_ANS'); // Action not specified
      return false;
    }

    // Now we can manage any supported request. If the request doesn't match any case the function return just true
    // jBackend initializes the response to null so if no plugin matches the request the final result is still null
    // and the exception can be managed by jBackend itself
    switch ($resource)
    {
      case 'comments':
        if ($action == 'get')
        {
          return $this->actionComments($response, $status);
        }
        break;
      case 'comment':
        if ($action == 'get')
        {
          return $this->actionComment($response, $status);
        }
        break;
      case 'create':
        if ($action == 'post')
        {
          return $this->actionCreate($response, $status);
        }
        break;
    }

    return true;
  }
}
