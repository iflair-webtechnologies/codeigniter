<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Serves as a intermediate class to load views as an AJAX /PHP function  
 * called from controller or AJAX call. must act as a controller to pass the proper data to the view
 */
class View_loader {
    
    var $CI;
    
    function __construct(){
        
        $this->CI =& get_instance(); 
    }
    
    function store_info($store_id,$output = true){
        $this->CI->load->model('store_m');
        $this->CI->load->model('review_m');
        $this->CI->load->model('country_m');
        
        $data['total_reviews'] = $this->CI->review_m->count_store_review($store_id);
        $data['total_ratings'] = $this->CI->review_m->count_store_ratings($store_id);
        
        if($store_id){
            $info = $this->CI->store_m->get_store($store_id);
        }
        
        $data['info'] = isset($info) ? $info : 0;
        
        ob_start();
        $this->CI->load->view('partial/store_info',$data);
        $out = ob_get_contents();
        ob_end_clean();
        if($output) echo $out;
        
        return $out;
    }
    
    
    function manager_deals($store_id,$output = true){
        $this->CI->load->model('deal_m');
       
        $data['live_deals'] = $this->CI->deal_m->get_deals(array('store_id' => $store_id, 'status' => 'live','manager'=>true));
        $data['expired_deals'] = $this->CI->deal_m->get_deals(array('store_id' => $store_id, 'status' => 'expired'));
        $data['draft_deals'] = $this->CI->deal_m->get_deals(array('store_id' => $store_id,'status' => 'draft'));
        
        ob_start();
        $this->CI->load->view('partial/manager_deals',$data); 
        $out = ob_get_contents();
        ob_end_clean();
        if($output) echo $out;
        
        return $out;
    }
    
    function manage($store_id,$output = true){
        
        $this->CI->load->model('review_m');
        
        $data['reviews'] = $this->CI->review_m->get_reviews();
        
        ob_start();
        
        $this->CI->load->view('admin/manage',$data); 
        
        $out = ob_get_contents();
        
        ob_end_clean();
        
        if($output) echo $out;
        
        return $out;
    }
    
    function stock_photos($term_id,$output = true){
        $this->CI->load->model('media_m');
        
        $data['photos'] = $this->CI->media_m->get_related_public_assets($term_id);

        ob_start();
        $this->CI->load->view('partial/stock_photos',$data); 
        $out = ob_get_contents();
        ob_end_clean();
        if($output) echo $out;
        
        return $out;
        
        
    }
    
    /**
     * Prints out the search deals in a consistent format given by a consistent
     * results input
     * Maybe add parameter to create row development (home vs search/deal/store page)
     * 
     * @param type $results
     * @param type $output
     * @return type 
     */
    
    function search_deals($results,$params,$output = true,$load_store = false,$AMPM=false){			
			
        $data['results'] = $results;
        $data['params'] = $params;
        
        if($AMPM) { $data['ampm'] = "sortAMPM"; }
    
        ob_start();
        if($load_store) {
          	$this->CI->load->view('partial/store_deals',$data);
        } else {
        	$this->CI->load->view('partial/search_deals',$data);
        }
        $out = ob_get_contents();
        ob_end_clean();
        if($output) echo $out;
        
        return $out;
        
    }
    
    /**
     * Prints out the deal in a consistent format given by a consistent
     * Actions define button e.g. addtocart - add to cart and quickview option,suspend - suspend button,edit - edit,delete and publish button,renew - renew button
     */
    
    function view_deal($deal,$actions='addtocart',$output = true){
        
       $data['deal'] = $deal;    	
       $data['actions'] = $actions;    
              
        ob_start();
        $this->CI->load->view('partial/view_deal',$data);
    	$out = ob_get_contents();
    	ob_end_clean();
    	if($output) echo $out;
    	return $out;
    }
    
    /**
     * Prints out the store in a consistent format given by a consistent
     */
    
    function view_store($store,$output = true){
        $this->CI->load->model('country_m');
    	$data['store'] = $store;
    
    	ob_start();
    	$this->CI->load->view('partial/view_store',$data);
    	$out = ob_get_contents();
    	ob_end_clean();
    	if($output) echo $out;
    
    	return $out;
    
    }

    function askquestion($store,$user_store_id,$output = true)
    {
        //$this->CI->load->model('review_m');
        
        //$data['reviews'] = $this->CI->review_m->get_reviews();
        $data['store'] = $store;
        $data['user_store_id'] = $user_store_id;
        
        ob_start();
        
        $this->CI->load->view('admin/askquestion',$data); 
        
        $out = ob_get_contents();
        
        ob_end_clean();
        
        if($output) echo $out;
        
        return $out;
    }
    
    function review()
    {   
       $this->CI->load->model('review_m');
                       
       $data['reviews'] = $this->CI->review_m->get_reviews('review'); 
       
       echo "<pre>";
       print_r($data);
              
       $this->CI->load->view('admin/review',$data);        
    }
    
    function current_ratings($object_id,$object_type,$user_id)
    {   
       $this->CI->load->model('review_m');
                       
       $data['ratings'] = $this->CI->review_m->get_rating($object_id,$object_type);
       
       
       $n = count($data['ratings']); //NUMBER OF VOTES
        
       if($n==1){
            $v = 'vote';
        } else {
            $v = 'votes';
        }
        
        $x = "";

        foreach($data['ratings'] as $ratings)
        {
            $rr = $ratings->rating; //EACH RATING FOR THE CONTENT
            $x += $rr; 
        }

        if($n){
            $rating = $x/$n; //THE AVERAGE RATING (UNROUNDED)
        } else {
            $rating = 0; //SET TO PREVENT INVALID DIVISION OF 0 ERROR WHICH WOULD BE THE NUMBER OF RATINGS HERE
        }

            $dec_rating = round($rating, 1); //ROUNDED RATING TO THE NEAREST TENTH
            $stars = "";
            
            //SHOWS THE FULL NUMBER OF STARS (Ex: 3.5 = 3stars)
            for($i=1; $i<=floor($rating); $i++){
                $stars .= '<div class="star_rating" id="'.$i.'"></div>';
            }

            //SHOWS THE RATING OF THE USER, IF RATING HAS BEEN SUBMITTED BEFORE
            $ip = $_SERVER["REMOTE_ADDR"];
            $user_id = $user_id;

            $data['has_rated'] = $this->CI->review_m->has_rated($object_id,$object_type,$ip,$user_id);
            
            $r = "";
            $id = "";
            foreach($data['has_rated'] as $ratings)
            {
               $r = $ratings->rating; //EACH RATING FOR THE CONTENT
               $id = $ratings->id;
            }
            
            
            //$r = mysql_fetch_assoc($data['has_rated']);
            $y = "";
            
            if($r){
                    $y = 'You rated : <b>'.$r.'</b>';
            }

        $data =  '<div class="r">
        <div class="rating_stars">'.$stars.'</div>
        <div class="transparent">
        <div class="star_rating" id="1"></div>
        <div class="star_rating" id="2"></div>
        <div class="star_rating" id="3"></div>
        <div class="star_rating" id="4"></div>
        <div class="star_rating" id="5"></div>
        <div class="votes">('.$dec_rating.'/5, '.$n.' '.$v.') '.$y.'</div>
        </div>
        </div><input id="id" type="hidden" value="'.$id.'">';
        
       echo $data;
      
    }
}
// END View Loader Class