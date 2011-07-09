<?php
define( '_JEXEC', 1 );
define('JPATH_BASE', dirname(__FILE__) );
define( 'DS', DIRECTORY_SEPARATOR );
/* Required Files */
require_once ( JPATH_BASE .DS.'includes'.DS.'defines.php' );
require_once ( JPATH_BASE .DS.'includes'.DS.'framework.php' );
/* To use Joomla's Database Class */
require_once ( JPATH_BASE .DS.'libraries'.DS.'joomla'.DS.'factory.php' );
/* Create the Application */
require_once("configuration.php");

require_once("formatting.php");

/* Create the Application */
$mainframe =& JFactory::getApplication('site');


jimport('joomla.user.helper');
jimport('joomla.mail.helper');




// connect to wordpress
// get user import user to joomla user
// find posts by user, import each post to joomla
// import tags for each post
//more stuff
//fix bugs
//go take a break and feel cool



class wpMigrate {
  public  $_id = null;
  public  $_user = null;
  public  $_password = null;
  public  $_password_hash = null;
  public  $_email = null;
  public  $_username = null;
  public  $_k2Category = 5;
    
  public function __construct($id) {
       
       $db =& JFactory::getDBO();
       $query = "select * from `wp_users` where `id` = '{$id}'";
       $db->setQuery($query);
       $this->_user = $db->loadObjectList();
        
       if($this->_user) {
          $this->_id = $id;
          $this->_password = JUserHelper::genRandomPassword();
          $this->_password_hash = JUtility::getHash($this->_password);
          $this->_email = $this->_user['0']->user_email;
          $this->_username = $this->_user['0']->user_login;
          $this->_name = $this->_user['0']->display_name;
          $this->_registerDate = $this->_user['0']->user_registered;
       
       
        } 
       
    }  
    
    private  function save_image($img,$itemID){
    $ch = curl_init ($img);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
    $rawdata=curl_exec($ch);
    curl_close ($ch);
    if(file_exists($fullpath)){
        unlink($fullpath);
    }
    $path = "media/k2/items/";
    $name = md5('Image'.$itemID);
    //settings
    //src
    $srcname = md5('Image'.$itemID).".jpg";
    $fullpath = $path.'src/'.$srcname;
    if (!file_exists($fullpath)) {
    $fp = fopen($fullpath,'x');
    fwrite($fp, $rawdata);
    }
    //    
    $sizes = array(XL,L,M,S,XS);
    foreach($sizes as $size) {
       $image = $name."_".$size.".jpg";
    $fullpath = $path.'cache/'.$image;
        if (!file_exists($fullpath)) {
    $fp = fopen($fullpath,'x');
    fwrite($fp, $rawdata); 
      fclose($fp);
    }
    }
   
    
  
}
    
    
    
     private function caption($content, $itemID) {
        //$match_count = preg_match_all('/\[caption.*caption="(.*)"\](.*)\[\/caption\]/smU', $content, $matches);
        
        //for ($i=1; $i < $match_count; $i++) {
        //if($i==1){
        
        $content = strip_tags($content, '<caption><img><a>');
        //change caption to tag easy way
        $content = str_replace('[caption', '<caption', $content);
        $content = str_replace('"]<a', '"><a', $content);
        $content = str_replace('[/caption]', '</caption> $$$$', $content);

        $match = explode('$$$$', $content);
        $$content = $match[0];
        // echo $content;
        //load all html into a domdocument
        // Create the DOM Document
        $dom = new DOMDocument();
// Load the HTML
        @$dom->loadHTML($content);
// Get the paths
        foreach ($dom->getElementsByTagName('a') as $node) {
            if (!$node->hasAttribute('href')) {
                continue;
            }

            if (preg_match('~\bwp-content\b~', $node->getAttribute('href'))) {
                $img = $node->getAttribute('href');
                //process images
                $this->save_image($img, $itemID);
            }
        }

        foreach ($dom->getElementsByTagName('caption') as $node) {
            if ($node->hasAttribute('caption')) {
                $caption = $node->getAttribute('caption');
            }
           
       // } //if
        
        //} //for
            
        }

         //remove caption

        return $caption;
    }
    
     private function morecaptions($content,$title)
    {
         
        $match_count = preg_match_all('/\[caption.*caption="(.*)"\](.*)\[\/caption\]/smU', $content, $matches);

        if($match_count) {echo 'Found ' . $match_count .' more caption(s)... Processing'.$title;}
        
        if($match_count) {
            $replacements = array();            
            
            for ($i=0; $i < $match_count; $i++) { 
                
                
                $replacements[] = array($matches[0][$i],'<div class="itemImageBlock"><span class="itemImage">'. $matches[2][$i] . '</span><span class="itemImageCaption">' . $matches[1][$i] . '</span><div class="clr"></div></div>');
            }
            
            foreach ($replacements as $replace) {
                
                $content = str_replace($replace[0], $replace[1], $content);
            }
        }

        return $content;
    }
    
    
    
    
 private function wp2jtags($id) {
      $db = & JFactory::getDBO();
 $query = "SELECT wp_terms.`name`, 
	wp_term_relationships.object_id, 
	wp_terms.term_id
FROM wp_term_relationships INNER JOIN wp_term_taxonomy ON wp_term_relationships.term_taxonomy_id = wp_term_taxonomy.term_taxonomy_id
	 INNER JOIN wp_terms ON wp_terms.term_id = wp_term_taxonomy.term_id WHERE wp_term_relationships.object_id = {$id}";    
     $db->setQuery($query);
     
     $tag = $db->loadObjectList();
     
    
     for ($i=1; $i < count($tag); $i++) {
         $query = "select id from `jos_k2_tags` where id = '{$tag[$i]->term_id}'";
		 $db->setQuery($query); //produces dup id's errors
        
$checkId = $db->loadResult();
if ($checkId) {} else{
         $query = "insert into `jos_k2_tags` (`id`, `name`, `published`) values ('{$tag[$i]->term_id}', '{$tag[$i]->name}', '1')";
         $db->setQuery($query); //produces dup id's errors
          $entertags = $db->query();}
		  
         $query2 = "insert into `jos_k2_tags_xref` ( `tagID`, `itemID`) values ('{$tag[$i]->term_id}','{$tag[$i]->object_id}')";
             $result2 = $db->setQuery($query2);
            @$entertagsref = $db->query();
       }
     
    
 }   
    
    
 function wp2jUser() {
        //could generate new ID, but letting id auto incremete and we can pull that ID and return but for now lets just user wordpress id's
        $db = & JFactory::getDBO();
        $query = "insert into `jos_users` ( `id`, `password`, `username`, `lastvisitDate`, `registerDate`, `email`, `usertype`, `name`, `gid`) values ( '{$this->_id}', '{$this->_password_hash}', '$this->_username', '0000-00-00 00:00:00', '{$this->_registerDate}', '{$this->_email}', 'Author', '{$this->_name}', '19')";
        $query = $db->setQuery($query);

        $result = $db->query();
        if (!$result) {
            die('wp2juser first set' . mysql_error());
        }
        $query = "insert into `jos_core_acl_aro` (`section_value`, `value`, `order_value`, `name`, `hidden`) values ('users', '{$this->_id}', '0', '$this->_username', '0')";
        $query = $db->setQuery($query);
        
        $result = $db->query();
        $aclID = $db->insertid();
        if (!$result) {
            die('wp2juser second set' . mysql_error());
        }

        $query = "insert into `jos_core_acl_groups_aro_map` (`group_id`, `aro_id`) values ('19', '{$aclID}')";
        $query = $db->setQuery($query);

        $result = $db->query();
        if (!$result) {
            die('wp2juser third set' . mysql_error());
        }
    }



//wp2jUser
    
  function wp2jPosts() {
    $db = & JFactory::getDBO();
    $query = "select * from `wp_posts` where `post_author` = '{$this->_id}'AND `post_type` = 'post' order by `ID` desc";
    $db->setQuery($query);


    $results = $db->loadObjectList();
    if (!$results) {
        echo 'no posts for '.$this->_username ."\n";
    }

    foreach ($results as $post) {

        $content = clean_pre($post->post_content);
         
        $content = wpautop($post->post_content);
        /*First caption is created into k2 image and caption for sick ass thumbnails*/
        if(preg_match('/\[caption/', $content)){
        $caption = $this->caption($content,$post->ID);
       //removing first caption
        $content = preg_replace('/\[caption.*](.*)\[\/caption\]/','', $content, 1); // limit first caption
        }
        

        if(preg_match('/\[caption/', $content)){
          $content = $this->morecaptions($content, $post->post_title);
        }
        
        //getting the extra fields
        $query = "select meta_value from `wp_postmeta` where `post_id` = '{$post->ID}' AND `meta_key` = 'dsq_thread_id'";
        $db->setQuery($query);
        $extrafields = $db->loadResult();
        //easy way instead of putting in array and generating JSON
        $extrafields = '[{"id":"1","value":"'.$extrafields.'"}]';
        // clearing up mess
        $content = str_replace('<p> </p>', '', $content);
        $content = preg_replace('/\<p\>\s+\<\/p\>/','', $content); // cleaning double spaces
        $content = preg_replace('/\<p\> \<\/p\>/','', $content); // cleaning double spaces
        //$content = str_replace('</span></div>UNIQUE</p>', '</span><div class="clr"></div></div>', $content);
        $content = mysql_escape_string($content); //mysql_real_escape will fail because we are not techinally connected to the database
        $pattern = '/<p><!--more--><\/p>/';   // read more anyone
        
        $content = preg_split($pattern, $content);
        $post->post_title = mysql_escape_string($post->post_title);
        $caption = mysql_escape_string($caption);
        
        $alias = JFilterOutput::stringURLSafe($post->post_name);
        $query = "insert into `jos_k2_items` ( `id`,`catid`, `introtext`,`fulltext`, `title`,  `alias`, `created`, `created_by`, `extra_fields`,`image_caption`) values ('{$post->ID}','{$this->_k2Category}', '{$content[0]}', '{$content[1]}' , '{$post->post_title}', '{$alias}', '{$post->post_date}', '{$this->_id}', '{$extrafields}', '{$caption}')";
        $db->setQuery($query);
        $result = $db->query();

        if (!$result) {
            die('POst' . mysql_error());
        }
        
        
       $this->wp2jtags($post->ID) ;
        
        
    }
  }
}

 


  $db =& JFactory::getDBO();
       $query = "select * from `wp_users`";
       $db->setQuery($query);
       $users = $db->loadObjectList();
 
       
       foreach ($users as $user){
       $user = New wpMigrate($user->ID);
$user->wp2jUser();
$user->wp2jPosts(); 
       }
      






?>
