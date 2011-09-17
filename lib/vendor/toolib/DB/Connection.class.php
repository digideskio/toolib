<?php
/*
 *  This file is part of PHPLibs <http://phplibs.kmfa.net/>.
 *  
 *  Copyright (c) 2010 < squarious at gmail dot com > .
 *  
 *  PHPLibs is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *  
 *  PHPLibs is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  
 *  You should have received a copy of the GNU General Public License
 *  along with PHPLibs.  If not, see <http://www.gnu.org/licenses/>.
 *  
 */

/**
 * @brief Module implementing ORM concept over mysql.
 */
namespace toolib\DB;

require_once __DIR__. '/../EventDispatcher.class.php';
require_once __DIR__ . '/../Exceptions.lib.php';

use toolib\NotConnectedException;

/**
 * @brief Interface to manage database connection.
 * 
 * Supports delayed connection, prepared statements,
 * delayed preparation and centralized error handling.
 */
class Connection
{
    /**
     * @brief Connection handler
     * @var \MySQLi
     */
    static private $dbconn = null;
    
    /**
     * @brief Connection options
     * @var array
     */
    static private $connection_options = null;

    /**
     * @brief The array with all statemenets
     * @var array
     */
    static private $stmts;

    /**
     * @brief Set error handler function
     * @var callable
     */
    static private $error_handler_func;

    /**
     * @brief Delayed preparation flag
     * @var boolean
     */
    static private $delayed_preparation;
    
    /**
     * @brief Delayed connection flag
     * @var boolean
     */
    static private $delayed_connection;

    /**
     * @brief Events dispatcher
     * @var \toolib\EventDispatcher
     */
    static private $events = null;

    /**
     * @brief Packet size when sending binary packets
     * @var number
     */
    static private $max_packet_allowed = null;
    
    /**
     * @brief Queries that must be run after connection
     * @var array
     */
    static private $initialization_queries = array();

    /**
     * @brief Get the events of this object.
     *  
     * Events are announced through an EventDispatcher object. The following
     * events are valid:
     *  - @b connected: Executed after a completed connection.
     *  - @b disconnected: Executed after disconnection.
     *  - @b error : Executed on any internal error.
     *  - @b query: Perform a direct query on the connection.
     *  - @b stmt.declared: Request preparation of a statement.
     *  - @b stmt.prepared: A requested statement was prepared.
     *  - @b stmt.executed: A prepared statement was executed.
     * .
     * @return \toolib\EventDispatcher The object with all events.
    */
    static public function events()
    {   
        if (self::$events === null)
            self::$events = new \toolib\EventDispatcher(array(
	        	'connected',
	        	'disconnected',
	        	'error',
	        	'query',
	        	'stmt.declared',
	        	'stmt.prepared',
	        	'stmt.executed',
	        	'stmt.released',
            ));
        return self::$events;
    }

    /**
     * @brief Initialize db connection.
     * 
	 * @param string $server The dns or ip of the server to connect at.
	 * @param string $user The user to use for authentication.
	 * @param string $pass The password that will be used for authentication.
	 * @param string $schema The schema to use as default for this connection.
	 * @param boolean $delayed_preparation Flag if delayed preparation should be used to
	 *   improve performance.
	 * @param boolean $delayed_connection Flag if delayed connection should be used to
	 *   improve performance.
     * @return boolean
     * 	- @b false If there was any error.
     *	- @b true If everything went ok. 
     */
    static public function connect($server, $user, $pass, $schema, $delayed_preparation = true, $delayed_connection = false)
    {   
        self::$delayed_preparation = $delayed_preparation;
        self::$delayed_connection = $delayed_connection;
        self::$max_packet_allowed = null;                

        // Create events dispatcher if it does not exist
        self::events();
    
        // Disconnect
        self::disconnect();
    
        // Prepare conection
        self::$connection_options = array(
        	'host' => $server,
        	'username' => $user,
        	'password' => $pass,
        	'schema' => $schema
        );
        
        // Connect if it is needed
        if (!self::$delayed_connection)
        	return self::assureConnect();

        // For delayed connection we assume ok
        return true;
    }
    
    /**
     * @brief Execute queries that initialize connection.
     * 
     * The execution of this querie may be postponed until the actual
     * connection is done. It is used for queries that initialize
     * connection, like setting up time_zone, character set. etc.
     * @param string $query The query that will be executed by the server.
     * @return boolean
     * 	- @b false If there was any error.
     *	- @b true If everything went ok. 
     */
    static public function initializationQuery($query)
    {
    	// If there is connection execute it
    	if (is_object(self::$dbconn))
    		return self::query($query);

    	// or push it to stack
    	self::$initialization_queries[] = $query;
    	return true;
    }
    
    /**
     * @brief Assures that the connection is initialized
     */
    static private function assureConnect()
    {
    	if (is_object(self::$dbconn))
    		return true;	// We are connected
    	if (self::$connection_options === null)
    		return false;	// We are not on (pre)connection state.
    		
    	// Try to connect
    	self::$dbconn = new \MySQLi(
    		self::$connection_options['host'], 
    		self::$connection_options['username'], 
    		self::$connection_options['password'],
    		self::$connection_options['schema']
    	);
    	
        if (self::$dbconn->connect_error) {
            self::raiseError('Error connecting to database. ' . self::$dbconn->connect_error);
            self::$dbconn = null;
            self::$connection_options = null;
            return false;
        }
        self::$stmts = array();
        self::$events->notify('connected', self::$connection_options);
        
        // Execute initialization queries
        foreach(self::$initialization_queries as $query)
        	self::query($query);
        self::$initialization_queries = array();
        
        return true;
    } 

    /**
     * @brief Disconnect db connection.
     * 
     * @return boolean
     * 	- @b false If there was any error.
     *	- @b true If everything went ok. 
     */
    static public function disconnect()
    {   
    	self::$connection_options = null;
        if (self::$dbconn !== null) {
        	self::$dbconn = null;            
        	self::events()->notify('disconnected');
        }
        return true;
    }

    /**
     * @brief Check if it is connected
	 * @return boolean
	 *	- @b true if it is connected.
	 *	- @b false if disconnected.
	 */
    static public function isConnected()
    {   
        return (self::$dbconn !== null);
    }

    /**
     * @brief Get the max_packet_allowed for this connection
     * @return integer The max_allowed_packet that is asked from te server.
     */
    static public function getMaxAllowedPacket()
    {
    	if (self::$max_packet_allowed !== null)
    		return self::$max_packet_allowed;
    	
    	$res = self::queryFetchAll('SELECT @@max_allowed_packet');
    	return self::$max_packet_allowed = $res[0][0]; 
    }
    
    /**
     * @brief Change the default character set of the connection
     * @param string $charset The default charset to be used for this connection
     * @throws NotConnectedException if there is no connection.
     */
    static public function setCharset($charset)
    {   
        if (self::$dbconn === null)
        	if (!self::assureConnect())
            	throw new NotConnectedException(__CLASS__ . '::' . __FUNCTION__ . '() demands established connection!');

        if (!self::$dbconn->setCharset($charset)) {
            self::raiseError('Cannot change the character set. ' . self::$dbconn->error);
            return false;
        }
        return true;
    }

    /**
     * @brief Get the mysqli connection object
     * @throws NotConnectedException if there is no connection.
     * @return mysqli Object of the link used for tihs connection.
     */
    static public function getLink()
    {   
        if (self::$dbconn === null)
        	if (!self::assureConnect())
            	throw new NotConnectedException(__CLASS__ . '::' . __FUNCTION__ . '() demands established connection!');

        return self::$dbconn;
    }

    /**
     * @brief Escape a string for mysql usage
     * @param stirng $str The string to be escaped.
     * @throws NotConnectedException if there is no connection.
     */
    static public function escapeString($str)
    {	
        if (self::$dbconn === null)
        	if (!self::assureConnect())
            	throw new NotConnectedException(__CLASS__ . '::' . __FUNCTION__ . '() demands established connection!');

        return self::$dbconn->real_escape_string($str);
    }

    /**
     * @brief Get the id generated by the last insert command.
     * @throws NotConnectedException if there is no connection.
     * @return integer The actual number or null on error.
     */
    static public function getLastInsertId()
    {   
        if (self::$dbconn === null)
        	if (!self::assureConnect())
            	throw new NotConnectedException(__CLASS__ . '::' . __FUNCTION__ . '() demands established connection!');

        return self::$dbconn->insert_id;
    }

    /**
     * @brief It does the actual statement prepartion (used for delayed prepartion)
     */
    static private function assurePreparation($key)
    {
        // Check if it must be prepared now
        if (!isset(self::$stmts[$key]['handler'])) {
            // Prepare statement
            if (!($stmt = self::$dbconn->prepare(self::$stmts[$key]['query']))) {
                self::raiseError("Cannot prepare statement '" . $key . "'. " . self::$dbconn->error);
                // Release statement as it is invalid
                unset(self::$stmts[$key]);
                return false;
            }
            self::$stmts[$key]['handler'] = $stmt;

            self::$events->notify('stmt.prepared', array('key' => $key));
        }
        return true;
    }

    /**
	 * @brief Check if this key is already used in prepared statements
	 * @param string $key The key to be checked
	 * @return boolean
	 *	- @b true if it is already used.
	 *	- @b false if it is not used.
	 * @throws NotConnectedException if it is not connected
	 */
    static public function isKeyUsed($key)
    {   
        if (self::$dbconn === null)
        	if (!self::assureConnect())
            	throw new NotConnectedException(__CLASS__ . '::' . __FUNCTION__ . '() demands established connection!');
        return isset(self::$stmts[$key]);
    }

    /**
     * @brief Prepare a statment and save it internally
	 * @note prepare() will not actually compile statement
	 *   unless delayed_preparation is set to false at connect().
	 * @note If the query is wrong, the slot will be released automatically
	 *   at the time of the actual compilation.
	 * @param string $key The unique name of the prepared statement, this will be used to execute
	 * 	the statement too.
	 * @param string $query The query of the statement.
	 * @return boolean
	 *	- @b true if the statement was accepted for preparation.
	 *	- @b false on any error.
	 * @throws NotConnectedException if there is no connection.
	 */
    static public function prepare($key, $query)
    {   
        if (self::$dbconn === null)
        	if (!self::assureConnect())
            	throw new NotConnectedException(__CLASS__ . '::' . __FUNCTION__ . '() demands established connection!');

        // Check if the key is free
        if (isset(self::$stmts[$key])) {
        	self::raiseError('There is already a statement prepared with this key "' . $key . '".');
            return false;
        }
    
        // Create statement entry
        self::$stmts[$key] = array('query' => $query);
    
        // Statement declared
        self::$events->notify('stmt.declared', array('key' => $key, 'query' => $query));
    
        // Delayed preparation check
        if (self::$delayed_preparation === false)
            return self::assurePreparation($key);
    
        return true;
    }

    /**
     * @brief Release a prepared statement
	 * @param string $key The unique name that was used on prepare().
	 * @return boolean
	 *	- @b true If the statement was found released.
	 *	- @b false on any error
	 * @throws NotConnectedException if it is not connected
	 */
    static public function release($key)
    {   
        if (self::$dbconn === null)
        	if (!self::assureConnect())
            	throw new NotConnectedException(__CLASS__ . '::' . __FUNCTION__ . '() demands established connection!');

        // Check if the key is free
        if (!isset(self::$stmts[$key])) {
            self::raiseError('Cannot release the statement "' . $key . '" that does not exist.');
            return false;
        }
    
        // Check if it is prepared
        if (isset(self::$stmts[$key]['handler']))
            self::$stmts[$key]['handler']->close();
    
        // Free slot
        unset(self::$stmts[$key]);
    
        // Notify
        self::$events->notify('stmt.released', array('key' => $key));
    
        return true;
    }

    /**
     * @brief Prepare multiple statements with one call.
	 * @param array $statements All statement in associative array(key => statement, key => statement)..
	 * @throws NotConnectedException if there is no connection.
	 * @return boolean
	 *	- @b true If all statements were prepared
	 *	- @b false on any error
	 */
    static public function multiPrepare($statements)
    {
    	if (self::$dbconn === null)
    		if (!self::assureConnect())
            	throw new NotConnectedException(__CLASS__ . '::' . __FUNCTION__ . '() demands established connection!');

        foreach($statements as $key => $query)
            if (!self::prepare($key, $query))
                return false;
    
        return true;
    }

    /**
     * @brief Raise an error
     * @param string $msg The error message
     */
    static private function raiseError($msg)
    {	
    	// Notify about the error
        self::$events->notify('error', array('message' => $msg));

        // Log it as notice
        trigger_error($msg);
    }

    /**
     * @brief Execute a direct query in database and return result set.
	 * @param string $query The command to be executed on server
	 * @throws NotConnectedException if there is no connection.
	 * @return MySQLi_Result
	 *	- @b MySQLi_Result object with the result set
	 *	- @b false on any kind of error
	 */
    static public function query($query)
    {   
        if (self::$dbconn === null)
        	if (!self::assureConnect())
            	throw new NotConnectedException(__CLASS__ . '::' . __FUNCTION__ . '() demands established connection!');

        // Query db connection
        if (!$res = self::$dbconn->query($query)) {
            self::raiseError('toolib\DB\Connection::query(' . $query . ') error on executing query.' . self::$dbconn->error);
            return false;
        }
    
        // Command executed
        self::$events->notify('query', array('query' => $query));
        return $res;
    }

    /**
     * @brief Execute a direct query in database and get all results immediatly
     * @param string $query The command to be executed on server
	 * @return array
	 *	- An array with all records. Each record is an array with field values ordered
	 * by column order and by column name.
	 *	- @b false on any kind of error
	 * @throws NotConnectedException if there is no connection.
	 */
    static public function queryFetchAll($query)
    {   
		if (!$res = self::query($query))
			return false;

		/* mysqlnd Elegant solution
			$results = $res->fetch_all();
			$res->free_result();
		*/
            
        $results = array();
        while($row = $res->fetch_array())
            $results[] = $row;
        $res->free_result();
    
        return $results;
    }

    /**
     * @brief A macro for binding and executing a statement
	 * @param string $key The key of the statement that was used to prepare.
	 * @param array $param_data An associative array with all data that will be passed as parameters to prepared statement.
	 * 	Key of array must be the order of parameter in the statement or the name of parameter if it was declared
	 *  using names in the statement.
	 * @param array $param_types An associative array with type of data of previous array. If an entry is missing
	 * 	it defaults to string type.
	 * @return MySQLi_STMT
	 * 	- Statement handler object to fetch the results.
	 * 	- @b false on any kind of error.
	 * @note If you are executing statement that contains a binary parameter (marked with "b") the data are
	 *	send in chunks.
	 * @throws NotConnectedException if there is no connection.
	 */
    static public function execute($key, $param_data = null, $param_types = null)
    {	
        if (self::$dbconn === null)
        	if (!self::assureConnect())
            	throw new NotConnectedException(__CLASS__ . '::' . __FUNCTION__ . '() demands established connection!');

        // Check if statement exist
        if (!isset(self::$stmts[$key])) {
            self::raiseError('toolib\DB\Connection::execute("' . $key . '") The supplied statement ".
           	        "must first be prepared using toolib\DB\Connection::prepare().');
            return false;
        }

        // Assure preparation
        if (!self::assurePreparation($key))
            return false;
    
        // Bind parameters if it is needed
        if (($param_data !== null) && (count($param_data) !== 0)) {
        	$null = null;
            $params = array('');
            $norm_types = array();	//< Normalized types
            foreach($param_data as $index => $data) {
            	// Normalize type
            	$norm_types[$index] = (isset($param_types[$index]))?$param_types[$index]:'s';
            	if (($norm_types[$index] == 'b') && (strlen($param_data[$index]) < self::getMaxAllowedPacket()))
            		 $norm_types[$index] = 's';
                
            	$params[0] .= $norm_types[$index];
                if ($norm_types[$index] != 'b')
                	$params[] = & $param_data[$index];
                else
                    $params[] = & $null;
            }
            
            // Bind parameters
            if (!call_user_func_array(array(self::$stmts[$key]['handler'], 'bind_param'), $params)) {
            	self::raiseError('Cannot bind params to prepared statement "' . $key . '". ' . self::$stmts[$key]['handler']->error);
            	return false;
            }
            	
            // Send blob data
            if ($param_types !== null) {
                foreach($norm_types as $pos => $type) {
	                if ($norm_types[$pos] == 'b') {
	                    foreach(str_split($param_data[$pos], self::getMaxAllowedPacket()-5) as $data ) {
	                        if (!self::$stmts[$key]['handler']->send_long_data($pos, $data)) {
	                        	self::raiseError('Cannot send long data to prepared statement "' . $key . '". ' . self::$stmts[$key]['handler']->error);
	                        	return false;
	                        }
	                    }
	                }
                }
            }
        }
    
        // Execute statement
        if (!self::$stmts[$key]['handler']->execute())
        {   
            self::raiseError('Cannot execute the prepared statement "' . $key . '". ' . self::$stmts[$key]['handler']->error);
            return false;
        }
    
        self::$events->notify('stmt.executed', array_merge(array($key), (isset($args)?$args:array())));
    
        return self::$stmts[$key]['handler'];
    }

    /**
     * @brief A macro for executing a statement and getting all results in one query.
	 * @note This function is not slower than getting manually one-by-one rows and loading in memory.
	 * 	To use this function check the documentation of execute().
	 * @param string $key The key of the statement that was used to prepare.
	 * @param array $param_data An associative array with all data that will be passed as parameters to prepared statement.
	 * 	Key of array must be the order of parameter in the statement or the name of parameter if it was declared
	 *  using names in the statement.
	 * @param array $param_types An associative array with type of data of previous array. If an entry is missing
	 * 	it defaults to string type.
 	 * @return array
	 *	- An array with all records. Each record is an array with field values ordered
	 * by column order and by column name.
	 *	- @b false on any kind of error
	 * @throws NotConnectedException if there is no connection.
	 */
    static public function & executeFetchAll($key, $param_data = null, $param_types = null)
    {
        if (! ($stmt = self::execute($key, $param_data, $param_types))) {	
            $res = false;
            return $res;
        }

        if ($stmt->field_count <= 0) {
            $res = array();
            return $res;        // This statement has no result
        }

        // Get the name of fields
        if (($result = $stmt->result_metadata()) === null)
            return array();	// This query has no result set
        $fields = $result->fetch_fields();
        $result->close();

        // Bind results on each cell of bnd_res array
        $bnd_res = array_fill(0, $stmt->field_count, null);
        $bnd_param = array();
        foreach($bnd_res as $k => &$bnd)
        	$bnd_param[] = & $bnd;
        unset($bnd);
        $stmt->store_result();
        call_user_func_array(array($stmt, 'bind_result'), $bnd_param);

        // Get results one by one
        $array_result = array();
        while($stmt->fetch()) {
            $row = array();
            for($i = 0; $i < $stmt->field_count; $i++) {
                $row[$i] = $bnd_res[$i];
                $row[$fields[$i]->name] = & $row[$i];
            }
            $array_result[] = $row;
        }
        $stmt->free_result();

        return $array_result;
    }
};
