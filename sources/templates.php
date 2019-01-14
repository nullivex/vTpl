<?PHP
//------------------------------------------------------
//vMotion - Advanced Content Management Software
//	(C) 2005 Variance Software. All rights reserved.
//------------------------------------------------------


//-------------------------------------------------------------
//vTpl - v3.2.1
//	-fixed undefined index notices for globals
//-------------------------------------------------------------
//vTpl - v3.2
//	-rewrote cache features
//	-stripped needless code
//	-smartened caching
//	-improved file handling
//-------------------------------------------------------------
//vTpl -  v3.1
//	-added features for smart cache
//	-page will run full script and skip the html compile process
//	-flushes the cache and finishes running the script
//	-cache files are not stored with php now
//-------------------------------------------------------------
//vTpl - v3.0
//	-added template caching
//	-cleaned up all known bugs
//-------------------------------------------------------------
//vTpl - v2.7
//	-added trim() to output
//	-all tags are now lower case always
//	-str_ireplace is used for backwards compatability
//	-using variables in php templates from the script
//		-send variables with parse function 
//		-ex: $template->parse('x','x',array('var' => 'var'));
//		-in the template then define the var with php as follows
//		-ex: $var = {var};
//	-module_space() left for backwards compatability
//	-add $config['tpl_location'] to disable dynamic templating
//-------------------------------------------------------------
//vTpl - v2.6
//              -Modified for dynamic skinning
//              -Add full PHP support for templates
//	       -v2.6 - removed and if 0 clause
//--------------------------------------------------------------

//=================================
//Config
//=================================
$tpl['tpl_location'] 		= 'skin'; //leave blank for dynamic templating --no trailing slash
$tpl['tpl_cache']    		= '0'; //using template caching
$tpl['tpl_cache_folder']	= 'cache/'; //folder to store cached tpl's
$tpl['tpl_users']    		= true; //do you have a user databse
$tpl['tpl_cache_life']		= '2'; //life of a cache in hours
$tpl['tpl_session']			= '$_SESSION["user_id"]'; //to use for user identification
//=================================
//End Config
//=================================

if(!array_key_exists("ROOT_PATH",get_defined_constants())){
	define("ROOT_PATH","./");
}

if(isset($config)){
	foreach($tpl AS $key => $value){
		if(isset($config[$key])){
			$tpl[$key] = $config[$key];
		}
	}
}

class template {
	
	var $loaded_file = array();
	var $templates = array();
	var $body,$template,$post,$fileid,$valdiated,$build,$start,$end;
	
	//-------------------------------
	//Construct Class
	//-------------------------------
	function template(){
		global $tpl,$SKIN;
		
		//Start
		$this->start = microtime(TRUE);
		
		$this->info = $tpl;
		unset($tpl);
		
		//Select the User Session Get the Variable
		$session = $this->info['tpl_session'];
		if(isset($$session) && $this->info['tpl_users'] == 1){
			$this->info['member'] = $$session;
		}
		else
		{
			$this->info['member'] = '';
		}
		
		$this->post = count($_POST);
		$this->fileid = $this->cacheid();
		$this->validated = 0;
		$this->info['tpl_cache_life'] = 60 * 60 * $this->info['tpl_cache_life'];
		
		//Choose static or dynamic template location
		if($this->info['tpl_location'] != ''){
			$this->info['location'] = $this->info['tpl_location'];
		}
		else
		{
			$this->info['location'] = 'skin/'.$SKIN['real_name'];
		}
		
		//Clear All Caches Upon a Post var being set.
		if($this->post > 0 && $this->info['tpl_cache'] == 1){
			$this->clear_cache();
		}
		
		//Only Generate Without Post Vars
		if($this->post == 0 && $this->info['tpl_cache'] == 1){
			$this->validate();
		}
		
	
	}
	
//======================================================================================================================
//Cache Functions
//======================================================================================================================
	
	//--------------------------
	//Validate Cache
	//--------------------------
	function validate(){

		if(FALSE !== ($file = @file_get_contents($this->info['tpl_cache_folder'].$this->fileid.'.php.txt'))){
				
			eval($file);
					
			//Load Section if we have it
			if(isset($cache[$this->fileid])){
					
				$file = $cache[$this->fileid];
				unset($cache);
							
				//Check then the stamp on the file was
				preg_match('/{GENERATED:.*?}/i',$file,$matches);
				//Replace Rules
				$rules = array(
					"{GENERATED:",
					"}");
				$time = str_replace($rules,'',$matches[0]);

				if($time >= (time() - $this->info['tpl_cache_life'])){
								
					//Get Rid of The Generation Tags
					$rules = array(
						"/{URL:.*?}/i",
						"/{CACHEID:.*?}/i",
						"/{GENERATED:.*?}/i");
					$file = preg_replace($rules,'',$file);
									
					//Use Cache - Add Comment of Cache Use to File
					$end = round(microtime(TRUE) - $this->start,5);
					$file = '<!--Generated From Cache Url: '.$_SERVER['PHP_SELF'].' Timestamp: '.$time.' CacheID: '.$this->fileid.' Executed in: '.$end.'seconds -->
							'.$file;
									
					$this->build = trim($file);
					$this->validated = 1;
					echo $this->build;
					ob_flush();
									
				}
				else
				{
					$this->clear_cache();
				}							
			}
		}	
	}
	
	//--------------------------
	//Cache Id
	//---------------------------
	function cacheid(){
		//---------------------------------
		//Make Cache Id
		//---------------------------------
		if($this->info['tpl_users'] == 1 && $this->info['member'] != ''){
			//Add Username to cache id
			$cacheid = md5(rawurlencode($_SERVER['PHP_SELF'].$_SERVER['REQUEST_URI']).'-'.$this->info['member']);
		}
		else
		{
			$cacheid = md5(rawurlencode($_SERVER['PHP_SELF'].$_SERVER['REQUEST_URI']));
		}
		return $cacheid;
	}
	
	//--------------------------
	//Save Cache
	//---------------------------
	function cache(){
	
		//Get Filename
		$old = @file_get_contents($this->info['tpl_cache_folder'].$this->fileid.'.php.txt');
		$handle = fopen($this->info['tpl_cache_folder'].$this->fileid.'.php.txt','w');
		$cache  = '
		';
		$cache .= '//vMotion Cache File';
		$cache .= '
		';
		$cache .= '	$cache[\''.$this->fileid.'\'] = \'';
		$cache .= '
		';
		//Append some cache information
		$cache .= '{URL:'.$_SERVER['PHP_SELF'].'}';
		$cache .= '{CACHEID:'.$this->fileid.'}';
		$cache .= '{GENERATED:'.time().'}';
		$cache .= '
		';
		$end  = '
		';
		$end .= '\';';
		$end .= '
		';
		$this->body = stripslashes($this->body);
		$this->body = str_replace("'", "\'", $this->body);
		
		if($old != ''){
			//Check For A Previous Entry of the URI
			preg_match('/\$cache\[\''.$this->fileid.'\'\]/i',$old,$matches);
			if(isset($matches[0]) && $matches[0] != ''){
			
				$cache = '$cache[\''.$this->fileid.'\'] = \''.$this->body.'\';';
				//Replace the Old Entry
				preg_replace('@\$cache\[\''.$this->fileid.'\'\] = \'.*\';@si',$cache,$old);
				
				$cache = $old;
			}
			else
			{
				$cache = $old.$cache.$this->body.$end;
			}
		}
		else
		{				
			$cache = $old.$cache.$this->body.$end;
		}
		
		@fwrite($handle, $cache);
	}
	
	//---------------------
	//Erase Cache
	//---------------------
	function clear_cache($clear=FALSE){
		if($clear != 'all'){
			//Clear Single File
			@unlink($this->info['tpl_cache_folder'].$this->fileid.'.php.txt');
		}
		else
		{
			//Truncate Cache Folder
			$dir = opendir($this->info['tpl_cache_folder']);
			while(FALSE !== ($files = readdir($dir))){
				//Skip Uneeded Files
				$skip = array(
					'.',
					'..');					
				if(!in_array($files,$skip)){
					//Close Any open files
					@fclose($handle);
					@unlink($this->info['tpl_cache_folder'].$files);
				}
			}
		}
	}
	
//==================================================================================================================
//End Cache Functions
//==================================================================================================================
	
	//----------------------
	//Load Template File
	//----------------------
	function load_file($tpl){

		global $templates;
		
		//get file name
		$file = ROOT_PATH.$this->info['location'].'/'.$tpl.'.tpl.php';
		//loadfile
		if (isset($this->loaded_file[$tpl]) && $this->loaded_file[$tpl] == 1){
			//No Action
		}
		elseif(file_exists($file)){

			//Keep File on Hand
			$this->loaded_file[$tpl] = 1;

			require($file);
			$this->templates[$tpl] = $templates;
			unset($templates);
			
		}
		else
		{
		
		}
	}

	//-------------------
	//Parse Templates
	//-------------------
	function parse($tpl, $section, $tags=array()){

		global $SKIN,$config;
		
		if($this->validated == 0){

			//Load File
			$this->load_file($tpl);
			
			if(isset($this->templates[$tpl][$section])){
				$this->template = $this->templates[$tpl][$section];
			}
			//Add Globals
			if(isset($SKIN['real_name']) && isset($SKIN['img_dir'])){
				$tags['skin'] = $SKIN['real_name'];
				$tags['img_dir'] = $SKIN['img_dir'];
			}
			if(isset($config['site_name'])){
				$tags['site_name'] = $config['site_name'];
			}

	      	foreach ($tags as $tag => $data) {
				if(!function_exists('str_ireplace')){
					$tag = preg_quote($tag);
					$tag = (string) $tag;
					$this->template = preg_replace("/{" . strtolower($tag) . "}/i", addslashes($data), $this->template);
				}
				else
				{
	        		$this->template = str_ireplace("{" . strtolower($tag) . "}", addslashes($data), $this->template);
				}
			}
	        	
			$this->body .= $this->template;
			$this->template = '';
		}
	}
	
	//-------------------
	//Output Page
	//-------------------
	function output(){
		if($this->validated == 0 && $this->info['tpl_cache'] == 1){
			$this->body = stripslashes($this->body);
			eval('?>'.$this->body);
			$this->body = trim(ob_get_contents());
			ob_clean();
			echo $this->body;
			
			if($this->info['tpl_cache'] == 1 && $this->post == 0){
				$this->cache();
			}
		}
		else
		{
			$this->body = trim(stripslashes($this->body));
			eval ('?>'.$this->body);
		}
	}
	
	//---------------------------------------
	//For Backwards Compatability
	//---------------------------------------
	function module_space(){
		$this->parse('global','module_space',array());
	}
}
?>

