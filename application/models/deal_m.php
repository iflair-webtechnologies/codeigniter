<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');


class Deal_m extends CI_Model{
    
    
    function __contruct()
    {
        //Call the Model constructor       
        parent::__construct();
    }
    
    /**
     * Creates a new deal as a draft
     * 
     * @param type $data 
     */
    
    function create_deal($data,$store_id,$generate=false){
        $this->load->model('page_m');
        
        if($generate) { $data['status'] = 'live'; }
        else { $data['status'] = 'draft'; }
                
        $this->db->insert('deals',$data);
        
        $id = $this->db->insert_id();        
        $store_page = $this->page_m->get_page_by_object($store_id,'store');        
        $this->page_m->create_page($data['name'],$id,'deal',$store_page->slug);   // hmm get stores name... to pass as parent
        
        return $id;
    }
    
    function create_deal_time_track($data){
        
        $this->db->insert('deals_time_track',$data);        
        $id = $this->db->insert_id();     
        return $id;
    }
    
    /**
     * remove_deal: Removes the deal as well as the related terms and related assets adnd page
     * @param int $deal_id 
     */
    function remove_deal($deal_id){
        $this->load->model('term_m');
        $this->load->model('media_m');
        $this->load->model('page_m');
        
        $this->media_m->remove_media_relationship($deal_id,'deal');
        $this->term_m->remove_term_relationship($deal_id,'deal');
        $this->page_m->remove_page_by_object($deal_id,'deal');
        $this->remove_deal_track($deal_id);
        $this->db->where('id',$deal_id);
        $this->db->delete('deals');
    }
    
    function remove_deal_track($deal_id) {
        $this->db->where('deal_id',$deal_id);
        $this->db->delete('deals_time_track');
    }
    
    
    function update_deal($deal_id, $data = array()){
        
        $this->db->where('id',$deal_id);
        $this->db->update('deals',$data);
        
    }
    
    function update_deal_time_track($deal_id, $data = array(),$status='1'){        
        $last_deal_time_track = $this->get_last_time_track($deal_id,$status);
        //update last record
        if($last_deal_time_track) {
            $this->db->where('id',$last_deal_time_track->id);
            $this->db->update('deals_time_track',$data);        
        }
    }
    
    function get_last_time_track($deal_id,$status='1') {
        //get last ongoing record
        $this->db->select('*');
        $this->db->where('deal_id',$deal_id);
        $this->db->where('status',$status);
        $this->db->limit(1);
        $this->db->order_by('id', 'DESC'); 
        $query = $this->db->get('deals_time_track');        
        $last_deal_time_track = $query->row();
        return $last_deal_time_track;
    }
    
    /*function get_is_deal_time_track($deal_id) {
        //get last ongoing record
        $this->db->select('*');
        $this->db->where('deal_id',$deal_id);
        $this->db->order_by('id', 'DESC'); 
        $query = $this->db->get('deals_time_track');        
        $deal_num_row = $query->num_rows();
        return $deal_num_row;
    }*/
    
    function check_countdown_deal($deal_id) {
            $this->db->select('type');
            $this->db->where('id',$deal_id);
            $res = $this->db->get('deals')->row();
            if($res->type == 'countdown') {
                    return true;
            }
            return false;
    }
    
    function set_name($name)
    {
        $this->load->model('store_m');
        //$this->name = $name;
        //$this->db->insert('stores',$this);
        $this->store_m->wave();
    }
    
    function get_deal($deal_id){
        $this->db->select("deals.*,deals.id as deal_id,deals.name as deal_name, deals.status as deal_status, deals.description as deal_description,deals.start_time as deals_original_start_time,deals.end_time as deals_original_end_time,deals_time_track.*,deals_time_track.id as deal_time_track_id,stores.*,locations.*,pages.slug as page_slug, pages.parent_slug");
        $this->db->from('deals');
        $this->db->where('deals.id',$deal_id);
        $this->db->join('deals_time_track','deals_time_track.deal_id = deals.id','left');
        $this->db->join('stores','stores.id = deals.store_id','left');
        $this->db->join('locations','stores.location_id = locations.id','left');
        $this->db->join('pages','pages.object_id = deals.id AND pages.type = "deal"','left');
        //to fetch category slug        
        $this->db->select("(SELECT terms.slug FROM terms,term_relationships WHERE term_relationships.object_id = terms.id AND term_relationships.type = 'deal' AND term_relationships.object_id = '".$deal_id."') AS slug");        
        return $this->db->get()->row();
    }
    
    function get_deal_owner($deal_id) {
        
        if(isset($deal_id)) 
        {
           $this->db->select('store_id'); 
           $this->db->where('id',$deal_id);
           $query = $this->db->get("deals"); 
           $row = $query->result_array();
           
           foreach ($row as $val)
           {
             return  $val['store_id'];
           }
        }
        else
        {
             return  false;
        }
      
    } 
    
    function get_store_deals($store_id,$type=""){
        
        $this->db->where('store_id',$store_id);
        if($type) { $this->db->where('type',$type); }
        $query = $this->db->get('deals');
        
        return $query->result();
    }
        
    function get_recommended_deals($limit = 10) {
        $this->load->model('country_m');
        $currentCity = $this->country_m->get_current_city();
        $this->db->select("deals.*,deals.id as deal_id,deals.name as deal_name, deals.description as deal_description,deals.start_time as deals_original_start_time,deals.end_time as deals_original_end_time,deals_time_track.*, pages.slug, pages.parent_slug, stores.*");
        $this->db->from('deals');
        $this->db->where("deals.type = 'basic'");
        $this->db->join('deals_time_track','deals_time_track.deal_id = deals.id AND deals_time_track.status = "1"','left');
        $this->db->join('stores','stores.id = deals.store_id','left');
        $this->db->join('locations','stores.location_id = locations.id','left');        
        $this->db->where("locations.city = ".$currentCity);
        $this->db->join('pages','pages.object_id = deals.id AND pages.type = "deal"','left');
        $this->db->join('user_meta','user_meta.meta_value = stores.id AND user_meta.meta_key = "store_id"','left');
        $this->db->join('users','users.id = user_meta.user_id','left');
        // $this->db->limit($limit);
        // make sure the deal is live and in sellable hours
        $this->db->where("deals.status = 'live'");       
        $this->db->where("users.status = '1'"); 

   	// Skip deals with 0 left...
       	$this->db->where("deals.total != '' AND deals.total != '0'");
      
        //below code will show only currently ongoing deals
        $this->db->where("(deals_time_track.end_time > '".date('Y-m-d H:i:s',time())."' OR deals_time_track.end_time = '0000-00-00 00:00:00')" );    // ignore time while testing since deals are limited
        $this->db->where("deals_time_track.start_time <= '".date('Y-m-d H:i:s',time())."'");        
	$this->db->group_by("deals.id");// Eliminate duplicate deals
        $this->db->order_by("deals_time_track.start_time","asc");      
        
        /*$this->db->get()->result();
        echo $this->db->last_query();
        exit();*/
        
        return $this->db->get()->result();
    }
    
    // get purchase history deal start By Iflair on 8th may 2013 
    
    function get_sales_items_by_user_id($user_id,$limit="")
    { 
        $this->load->library('auth');
        switch($this->auth->get_role()) {
            case 'customer' :
                $this->db->select("deals.*,deals.id as deal_id,order_items.*, pages.slug, pages.parent_slug");
                $this->db->from('deals,order_items,users');        
                $this->db->where("deals.id = order_items.item_id");                                
                $this->db->join('pages','pages.object_id = deals.id AND pages.type = "deal"','left');                
                $this->db->join('user_meta','users.id = user_meta.user_id','left');        
                $this->db->where("users.id",$user_id);
                $this->db->where("order_items.user_id",$user_id);
                $this->db->group_by("order_items.voucher_no");
                if($limit) { $this->db->limit($limit, 0); }
                $this->db->order_by("order_items.id","desc");
            break;
            case 'store_admin' :
                $this->db->select("deals.*,deals.id as deal_id,order_items.*, pages.slug, pages.parent_slug, stores.id as store_id");
                $this->db->from('deals,order_items');        
                $this->db->where("deals.id = order_items.item_id");                
                $this->db->join('stores','stores.id = deals.store_id','left');
                $this->db->join('pages','pages.object_id = deals.id AND pages.type = "deal"','left');
                $this->db->join('user_meta','user_meta.meta_value = stores.id AND user_meta.meta_key = "store_id"','left');
                $this->db->join('users','users.id = user_meta.user_id','left');        
                $this->db->where("users.id",$user_id);   
                if($limit) { $this->db->limit($limit, 0); }
                $this->db->order_by("order_items.id","desc");
            break;
        } 
        /*$this->db->get();
        echo $this->db->last_query();*/
        return $this->db->get()->result();        
    }
    
    function get_gifted_items_by_user_id($user_id,$limit="") {                             
        $this->db->select("deals.*,deals.id as deal_id,order_items.*, pages.slug, pages.parent_slug,gifted_items.gifted_emails,order.process_date");
        $this->db->from('deals,order_items,users');        
        $this->db->where("deals.id = order_items.item_id");                                
        $this->db->join('pages','pages.object_id = deals.id AND pages.type = "deal"','left');                
        $this->db->join('user_meta','users.id = user_meta.user_id','left');        
        $this->db->join('gifted_items','gifted_items.id = order_items.gifted_id','left');
        $this->db->join('order','order.id = order_items.order_id','left');
        $this->db->where("users.id",$user_id);
        $this->db->where("order_items.gifted_id != ",0);
        $this->db->where("order_items.user_id",$user_id);
        $this->db->group_by("order_items.voucher_no");
        if($limit) {
            $this->db->limit($limit, 0);            
        }
        $this->db->order_by("order_items.id","desc"); 
        /*$this->db->get();
        echo $this->db->last_query();*/
        return $this->db->get()->result();
    }
    
    function get_sales_total_items_by_user_id($user_id)
    { 
        $this->load->library('auth');
        switch($this->auth->get_role()) {
            case 'customer' :
                $this->db->select("deals.*,deals.id as deal_id,order_items.*, pages.slug, pages.parent_slug");
                $this->db->from('deals,order_items,users');
                $this->db->where("deals.id = order_items.item_id");
                $this->db->join('pages','pages.object_id = deals.id AND pages.type = "deal"','left');
                $this->db->join('user_meta','users.id = user_meta.user_id','left');
                $this->db->where("users.id",$user_id);
                $this->db->where("order_items.user_id",$user_id);
                $this->db->group_by("order_items.voucher_no");
                $this->db->order_by("order_items.id","desc");
            break;
            case 'store_admin' :
                $this->db->select("deals.*,deals.id as deal_id,order_items.*, pages.slug, pages.parent_slug, stores.id as store_id");
                $this->db->from('deals,order_items');        
                $this->db->where("deals.id = order_items.item_id");        
                // $this->db->join('order_items','order_items.item_id = deals.id','left');
                $this->db->join('stores','stores.id = deals.store_id','left');
                $this->db->join('pages','pages.object_id = deals.id AND pages.type = "deal"','left');
                $this->db->join('user_meta','user_meta.meta_value = stores.id AND user_meta.meta_key = "store_id"','left');
                $this->db->join('users','users.id = user_meta.user_id','left');        
                $this->db->where("users.id",$user_id);   
                $this->db->order_by("order_items.id","desc");
            break;
        }              
        /*$this->db->get();
        echo $this->db->last_query();*/
        $sales_items = $this->db->get()->result();
        
        return count($sales_items);
    }
    
    // get purchase history deal end
    
    // get purchase history deal pagination start By Iflair on 8th may 2013 
    
       function get_purchase_history_by_user_pagination($params,$user_id){
		    	
        /*$this->db->where('user_id != '.$user_id);
        foreach($data as $val){
                        $vari[] =  $val;        		
        }
        $ids = implode(',',$vari);
        $where = "item_id IN (".$ids.")";
        $this->db->where($where);*/
        $this->load->library('auth');
        switch($this->auth->get_role()) {
            case 'customer':
                if($this->input->post('a_next')){
                    $params['more'] = true;
                    $params['limit_start'] = $this->input->post('a_next');
                }

                if($this->input->post('a_new_pagination')) {        	
                        $params['limit_start'] = 0;
                        $params['limit_end'] = $this->input->post('a_limit_end');
                }

                $this->db->select("deals.*,deals.id as deal_id,order_items.*, pages.slug, pages.parent_slug");
                $this->db->from('deals,order_items,users');

                $this->db->where("deals.id = order_items.item_id");
                
                $this->db->join('pages','pages.object_id = deals.id AND pages.type = "deal"','left');                                
                $this->db->join('user_meta','users.id = user_meta.user_id','left');
                $this->db->where("users.id",$user_id);
                $this->db->where("order_items.user_id",$user_id);
                $this->db->group_by("order_items.voucher_no");
                $this->db->order_by("order_items.id","desc");  

                $this->db->limit($params['limit_end'], $params['limit_start']);
                $item_detail = $this->db->get()->result();
                // echo $this->db->last_query();	
                $query = $this->db->query("SELECT FOUND_ROWS() AS 'total'");
                $total = $query->row()->total;

                $results = array(
                    'item_detail' => $item_detail,
                    'total' => $total
                );
                return $results;
            break;
            case 'store_admin':
                if($this->input->post('a_next')){
                    $params['more'] = true;
                    $params['limit_start'] = $this->input->post('a_next');
                }

                if($this->input->post('a_new_pagination')) {        	
                        $params['limit_start'] = 0;
                        $params['limit_end'] = $this->input->post('a_limit_end');
                }

                $this->db->select("deals.*,deals.id as deal_id,order_items.*, pages.slug, pages.parent_slug, stores.id as store_id");
                $this->db->from('deals,order_items');

                $this->db->where("deals.id = order_items.item_id");

                // $this->db->join('order_items','order_items.item_id = deals.id','left');
                $this->db->join('stores','stores.id = deals.store_id','left');
                $this->db->join('pages','pages.object_id = deals.id AND pages.type = "deal"','left');
                $this->db->join('user_meta','user_meta.meta_value = stores.id AND user_meta.meta_key = "store_id"','left');
                $this->db->join('users','users.id = user_meta.user_id','left');
                // $this->db->where("deals.status = 'live'");       
                $this->db->where("users.id",$user_id);       
                $this->db->order_by("order_items.id","desc");  

                $this->db->limit($params['limit_end'], $params['limit_start']);
                $item_detail = $this->db->get()->result();
                // echo $this->db->last_query();	
                $query = $this->db->query("SELECT FOUND_ROWS() AS 'total'");
                $total = $query->row()->total;

                $results = array(
                    'item_detail' => $item_detail,
                    'total' => $total
                );

                return $results;
            break;
        }        	        
    }
    
    function get_gift_monitor_by_user_pagination($params,$user_id){
		    	
        /*$this->db->where('user_id != '.$user_id);
        foreach($data as $val){
                        $vari[] =  $val;        		
        }
        $ids = implode(',',$vari);
        $where = "item_id IN (".$ids.")";
        $this->db->where($where);*/                            
        if($this->input->post('a_next')){
            $params['more'] = true;
            $params['limit_start'] = $this->input->post('a_next');
        }

        if($this->input->post('a_new_pagination')) {        	
                $params['limit_start'] = 0;
                $params['limit_end'] = $this->input->post('a_limit_end');
        }
        
        $this->db->select("deals.*,deals.id as deal_id,order_items.*, pages.slug, pages.parent_slug,gifted_items.gifted_emails,order.process_date");
        $this->db->from('deals,order_items,users');        
        $this->db->where("deals.id = order_items.item_id");                                
        $this->db->join('pages','pages.object_id = deals.id AND pages.type = "deal"','left');                
        $this->db->join('user_meta','users.id = user_meta.user_id','left');        
        $this->db->join('gifted_items','gifted_items.id = order_items.gifted_id','left');
        $this->db->join('order','order.id = order_items.order_id','left');
        $this->db->where("users.id",$user_id);
        $this->db->where("order_items.gifted_id != ",0);
        $this->db->where("order_items.user_id",$user_id);
        $this->db->group_by("order_items.voucher_no");
        $this->db->limit($params['limit_end'], $params['limit_start']);
        $item_detail = $this->db->get()->result();
        // echo $this->db->last_query();	
        $query = $this->db->query("SELECT FOUND_ROWS() AS 'total'");
        $total = $query->row()->total;

        $results = array(
            'item_detail' => $item_detail,
            'total' => $total
        );
        return $results;            	        
    }
    
    // get purchase history deal pagination end
    
    // get order history deal start By Iflair on 12th may 2013 
    
    function get_order_items_by_vendor_id($user_id,$limit="")
    {   
        //$this->db->select("deals.*,deals.id as deal_id,order_items.*, pages.slug, pages.parent_slug, stores.id as store_id");
        
        $this->db->select("count(*) as Order_Count");
	$this->db->select('deals.name');
        $this->db->from('deals,order_items');
        $this->db->where("deals.id = order_items.item_id");
        
        $this->db->join('order','order_items.order_id = order.id','left');
        $this->db->join('stores','stores.id = deals.store_id','left');
        $this->db->join('pages','pages.object_id = deals.id AND pages.type = "deal"','left');
        $this->db->join('user_meta','user_meta.meta_value = stores.id AND user_meta.meta_key = "store_id"','left');
        $this->db->join('users','users.id = user_meta.user_id','left');
        // $this->db->where("deals.status = 'live'");       
        $this->db->where("users.id",$user_id);   
        $this->db->group_by('order_items.item_id');
        if($limit) { $this->db->limit($limit, 0); }
        $this->db->order_by("order_items.id","desc");      
        /*$this->db->get()->result();
        echo $this->db->last_query();*/
        return $this->db->get()->result();
    }
    
    function get_order_sales_by_vendor_id($user_id,$limit="",$month_year_filter=array()) { 
        
        $this->db->select("SUM(price) as Order_Count");
	$this->db->select('order.process_date');
        $this->db->from('deals,order_items');
        $this->db->where("deals.id = order_items.item_id");        
        $this->db->join('order','order_items.order_id = order.id','left');
        $this->db->join('stores','stores.id = deals.store_id','left');        
        $this->db->join('user_meta','user_meta.meta_value = stores.id AND user_meta.meta_key = "store_id"','left');
        $this->db->join('users','users.id = user_meta.user_id','left');        
        $this->db->where("users.id",$user_id);
        $monthFilterArr = array();
        $yearFilterArr = array();
        foreach($month_year_filter as $filterDate) {
            $monthFilterArr[] = date('m',strtotime($filterDate));
            $yearFilterArr[] = date('Y',strtotime($filterDate));
        }
        $this->db->where_in("MONTH(process_date)",$monthFilterArr);       
        $this->db->where_in("YEAR(process_date)",$yearFilterArr);       
        $this->db->group_by("MONTH(process_date), YEAR(process_date), DAY(process_date)");
        if($limit) { $this->db->limit($limit, 0); }
        $this->db->order_by("order_items.id","desc");      
        /*$this->db->get()->result();
        echo $this->db->last_query();*/
        return $this->db->get()->result();
    }
    
    function get_vendor_order_items_by_month($user_id,$month_filter="",$year_filter="",$week_filter="",$day_filter = "")
    {        
        $this->load->library('report');
        $this->db->select("count(*) as Order_Count");
        $this->db->select('deals.name');
        $this->db->select('order.process_date');
        // $this->db->select("deals.*, count(order_items.*) as Order_Count, order.*");
	$this->db->from('deals,order_items');
        $this->db->where("deals.id = order_items.item_id");
        
        if($month_filter) {
            $this->db->where("date_format(date(process_date),'%m') = '".$month_filter."'");
        }
        
        if($week_filter) {            
            $weekDates = $this->report->getStartAndEndDateofWeek($week_filter, $month_filter, $year_filter);
            $this->db->where("date_format(date(process_date),'%d') >=", $weekDates[0]);
            $this->db->where("date_format(date(process_date),'%d') <=", $weekDates[1]);
        }
        
        if($day_filter) {
            $this->db->where("date_format(date(process_date),'%a') ", $day_filter);
        }
        
        if($year_filter) {
            $this->db->where("date_format(date(process_date),'%Y') = '".$year_filter."'");             
        }
        
        $this->db->join('order','order_items.order_id = order.id','left');
        $this->db->join('stores','stores.id = deals.store_id','left');
        $this->db->join('pages','pages.object_id = deals.id AND pages.type = "deal"','left');
        $this->db->join('user_meta','user_meta.meta_value = stores.id AND user_meta.meta_key = "store_id"','left');
        $this->db->join('users','users.id = user_meta.user_id','left');
        // $this->db->where("deals.status = 'live'");       
        $this->db->where("users.id",$user_id);   
        $this->db->group_by("order_items.item_id");        
        $this->db->order_by("order_items.id","desc");               
        /*$this->db->get()->result();
        echo $this->db->last_query();*/        
        return $this->db->get()->result();
    }
    
    function get_vendor_income_by_month($user_id,$month_filter="",$year_filter="",$week_filter="",$day_filter = "")
    {        
        $this->load->library('report');
        
        $this->db->select('deals.name');
        $this->db->select_sum('order_items.item_price');
        $this->db->select('order.process_date');
        // $this->db->select("deals.*, count(order_items.*) as Order_Count, order.*");
	$this->db->from('deals,order_items');
        $this->db->where("deals.id = order_items.item_id");
        
        if($month_filter) {
            $this->db->where("date_format(date(process_date),'%m') = '".$month_filter."'");
        }
        
        if($week_filter) {            
            $weekDates = $this->report->getStartAndEndDateofWeek($week_filter, $month_filter, $year_filter);
            $this->db->where("date_format(date(process_date),'%d') >=", $weekDates[0]);
            $this->db->where("date_format(date(process_date),'%d') <=", $weekDates[1]);
        }
        
        if($day_filter) {
            $this->db->where("date_format(date(process_date),'%a') ", $day_filter);
        }
        
        if($year_filter) {
            $this->db->where("date_format(date(process_date),'%Y') = '".$year_filter."'");             
        }
        
        $this->db->join('order','order_items.order_id = order.id','left');
        $this->db->join('stores','stores.id = deals.store_id','left');
        $this->db->join('pages','pages.object_id = deals.id AND pages.type = "deal"','left');
        $this->db->join('user_meta','user_meta.meta_value = stores.id AND user_meta.meta_key = "store_id"','left');
        $this->db->join('users','users.id = user_meta.user_id','left');
        // $this->db->where("deals.status = 'live'");       
        $this->db->where("users.id",$user_id);   
        $this->db->group_by("order_items.item_id");        
        $this->db->order_by("order_items.id","desc");       
        
        /*$this->db->get()->result();
        echo $this->db->last_query();*/
        
        return $this->db->get()->result();
    }
    
    function get_vendor_sales_by_month($user_id,$month_filter="",$year_filter="",$week_filter="",$day_filter = "")
    {        
        $this->load->library('report');
        
        $this->db->select('deals.name');
        $this->db->select_sum('order_items.item_quantity');
        $this->db->select('order.process_date');
        // $this->db->select("deals.*, count(order_items.*) as Order_Count, order.*");
	$this->db->from('deals,order_items');
        $this->db->where("deals.id = order_items.item_id");
        
        if($month_filter) {
            $this->db->where("date_format(date(process_date),'%m') = '".$month_filter."'");
        }
        
        if($week_filter) {            
            $weekDates = $this->report->getStartAndEndDateofWeek($week_filter, $month_filter, $year_filter);
            $this->db->where("date_format(date(process_date),'%d') >=", $weekDates[0]);
            $this->db->where("date_format(date(process_date),'%d') <=", $weekDates[1]);
        }
        
        if($day_filter) {
            $this->db->where("date_format(date(process_date),'%a') ", $day_filter);
        }
        
        if($year_filter) {
            $this->db->where("date_format(date(process_date),'%Y') = '".$year_filter."'");             
        }
        
        $this->db->join('order','order_items.order_id = order.id','left');
        $this->db->join('stores','stores.id = deals.store_id','left');
        $this->db->join('pages','pages.object_id = deals.id AND pages.type = "deal"','left');
        $this->db->join('user_meta','user_meta.meta_value = stores.id AND user_meta.meta_key = "store_id"','left');
        $this->db->join('users','users.id = user_meta.user_id','left');
        // $this->db->where("deals.status = 'live'");       
        $this->db->where("users.id",$user_id);   
        $this->db->group_by("order_items.item_id");        
        $this->db->order_by("order_items.id","desc");       
        
        /*$this->db->get()->result();
        echo $this->db->last_query();*/
        
        return $this->db->get()->result();
    }
    
    // get order history deal End 
    
    function get_rec_countdown_deals($limit = 10) {
        $this->load->model('country_m');
        $currentCity = $this->country_m->get_current_city();
        $this->db->select("deals.*,deals.id as deal_id,deals.name as deal_name, deals.description as deal_description,deals.start_time as deals_original_start_time,deals.end_time as deals_original_end_time,deals_time_track.*, pages.slug, pages.parent_slug, stores.* ");
        $this->db->from('deals');
        $this->db->where("deals.type = 'countdown'");
        $this->db->join('deals_time_track','deals_time_track.deal_id = deals.id AND deals_time_track.status = "1"','left');
        $this->db->join('stores','stores.id = deals.store_id','left');
        $this->db->join('locations','stores.location_id = locations.id','left');        
        $this->db->where("locations.city = ".$currentCity);
        $this->db->join('pages','pages.object_id = deals.id AND pages.type = "deal"','left');
        $this->db->join('user_meta','user_meta.meta_value = stores.id AND user_meta.meta_key = "store_id"','left');
        $this->db->join('users','users.id = user_meta.user_id','left');
        // $this->db->limit($limit);
        // make sure the deal is live and in sellable hours
        $this->db->where("deals.status = 'live'" ); 
        $this->db->where("users.status = '1'");
        //below code will show only currently ongoing deals
        $this->db->where("deals_time_track.end_time > '".date('Y-m-d H:i:s',time())."'" );    // ignore time while testing since deals are limited
        $this->db->where("deals_time_track.start_time <= '".date('Y-m-d H:i:s',time())."'");
        $this->db->group_by("deals.id");// Eliminate duplicate deals
        $this->db->order_by("deals_time_track.start_time","asc");

        return $this->db->get()->result();
    }

    function upcoming_countdown_deals($limit = 10)  {
        
        $this->db->select("deals.*,deals.id as deal_id,deals.name as deal_name, deals.description as deal_description,deals.start_time as deals_original_start_time,deals.end_time as deals_original_end_time,deals_time_track.*, pages.slug, pages.parent_slug, stores.* ");
        $this->db->from('deals');
        $this->db->where("deals.type = 'countdown'");
        $this->db->join('deals_time_track','deals_time_track.deal_id = deals.id AND deals_time_track.status = "1"','left');
        $this->db->join('stores','stores.id = deals.store_id','left');
        $this->db->join('pages','pages.object_id = deals.id AND pages.type = "deal"','left');
        $this->db->join('user_meta','user_meta.meta_value = stores.id AND user_meta.meta_key = "store_id"','left');
        $this->db->join('users','users.id = user_meta.user_id','left');
        // $this->db->limit($limit);
        // make sure the deal is live and in sellable hours
        $this->db->where("deals.status = 'live'" );   
        $this->db->where("users.status = '1'");
        //below code will show only currently ongoing deals
        $this->db->where("deals_time_track.end_time > '".date('Y-m-d H:i:s',time())."'" );    // ignore time while testing since deals are limited
        $this->db->where("deals_time_track.start_time <= '".date('Y-m-d H:i:s',time())."'");
        $this->db->group_by("deals.id");// Eliminate duplicate deals
        $this->db->order_by("deals_time_track.start_time","asc");

        return $this->db->get()->result();
    }

    function hotspot_deals($limit = 10) {
        $this->load->model('country_m');
        $currentCity = $this->country_m->get_current_city();
        $this->db->select("deals.*,deals.id as deal_id,deals.name as deal_name, deals.description as deal_description,deals.start_time as deals_original_start_time,deals.end_time as deals_original_end_time,deals_time_track.*, pages.slug, pages.parent_slug, stores.* ");
        $this->db->from('deals');
        $this->db->where("deals.type = 'basic'");
        $this->db->where("deals.original_price > deals.discount_price * 2");
        $this->db->join('deals_time_track','deals_time_track.deal_id = deals.id AND deals_time_track.status = "1"','left');
        $this->db->join('stores','stores.id = deals.store_id','left');
        $this->db->join('locations','stores.location_id = locations.id','left');        
        $this->db->where("locations.city = ".$currentCity);
        $this->db->join('pages','pages.object_id = deals.id AND pages.type = "deal"','left');
        $this->db->join('user_meta','user_meta.meta_value = stores.id AND user_meta.meta_key = "store_id"','left');
        $this->db->join('users','users.id = user_meta.user_id','left');
        // $this->db->limit($limit);
        // make sure the deal is live and in sellable hours
        $this->db->where("deals.status = 'live'" );
        $this->db->where("users.status = '1'");    
        //below code will show only currently ongoing deals
        $this->db->where("(deals_time_track.end_time > '".date('Y-m-d H:i:s',time())."' OR deals_time_track.end_time = '0000-00-00 00:00:00')" );    // ignore time while testing since deals are limited
        $this->db->where("deals_time_track.start_time <= '".date('Y-m-d H:i:s',time())."'");        
        $this->db->group_by("deals.id");// Eliminate duplicate deals
        $this->db->order_by("deals_time_track.start_time","asc");
        
        return $this->db->get()->result();
    }
    
    function get_countdown_deals($store_id,$type="countdown") {
        
        $this->db->select("deals.id");
        $this->db->from('deals');
        $this->db->where("deals.type = '".$type."'");
	$this->db->where("deals.status = 'live'");
        $this->db->where("deals.store_id = ".$store_id);
      	$this->db->where("deals.total != '' and deals.total is not null and deals.total != '0'");
        return $this->db->get()->result();
    }
    
    public function newest_deals($limit = 10) {
        $this->load->model('country_m');
        $currentCity = $this->country_m->get_current_city();
        $this->db->select("deals.*,deals.id as deal_id,deals.name as deal_name, deals.description as deal_description,deals.start_time as deals_original_start_time,deals.end_time as deals_original_end_time,deals_time_track.*, pages.slug, pages.parent_slug, stores.* ");
        $this->db->from('deals');
        $this->db->where("deals.type = 'basic'");
        $this->db->join('deals_time_track','deals_time_track.deal_id = deals.id AND deals_time_track.status = "1"','left');
        $this->db->join('stores','stores.id = deals.store_id','left');
        $this->db->join('locations','stores.location_id = locations.id','left');        
        $this->db->where("locations.city = ".$currentCity);
        $this->db->join('pages','pages.object_id = deals.id AND pages.type = "deal"','left');
        $this->db->join('user_meta','user_meta.meta_value = stores.id AND user_meta.meta_key = "store_id"','left');
        $this->db->join('users','users.id = user_meta.user_id','left');
        // $this->db->limit($limit);
        // make sure the deal is live and in sellable hours
        $this->db->where("deals.status = 'live'" );  
        $this->db->where("users.status = '1'"); 
   	// Skip deals with 0 left...
       	$this->db->where("deals.total != '' AND deals.total != '0'");
        //below code will show only currently ongoing deals
        $this->db->where("(deals_time_track.end_time > '".date('Y-m-d H:i:s',time())."' OR deals_time_track.end_time = '0000-00-00 00:00:00')" );    // ignore time while testing since deals are limited
        $this->db->where("deals_time_track.start_time <= '".date('Y-m-d H:i:s',time())."'");
        //newest
        $todayMidnight = mktime(0, 0, 0, date('n'), date('j'));
        $this->db->where("deals_time_track.start_time > '".date('Y-m-d H:i:s',$todayMidnight)."'");
        $this->db->group_by("deals.id");        
        $this->db->order_by("deals_time_track.start_time","asc");

        return $this->db->get()->result();
    }
    
    /**
     * TODO:: Will be a join with terms in order to get a more complex search 
     * - Mainly used on home page
     */
    
    public function get_deals($params = array()){
        $this->load->model('country_m');
        $currentCity = $this->country_m->get_current_city();
        $this->db->select("deals.*,deals.id as deal_id,deals.name as deal_name, deals.description as deal_description, pages.slug, pages.parent_slug, stores.* ");
        $this->db->from('deals');
        
        if(isset($params['store_id'])){
            $this->db->where("deals.store_id = ".$params['store_id'] );
        }
        if(isset($params['status'])){
            $this->db->where("deals.status = '".$params['status']."'" );
            if($params['status'] == 'live')
            {
                //$this->db->where("deals.end_time > ".date('Y-m-d H:i:s',time()) );
                if(!isset($params['manager'])){
                    $this->db->select("deals.start_time as deals_original_start_time,deals.end_time as deals_original_end_time,deals_time_track.*");
                    $this->db->join('deals_time_track','deals_time_track.deal_id = deals.id AND deals_time_track.status = "1"','left');
                    $this->db->where("deals.start_time < '".date('Y-m-d H:i:s',time())."'" );
                    // make sure the deal is live and in sellable hours
                    $this->db->where("deals.status = 'live'" );        
                    //below code will show only currently ongoing deals
                    $this->db->where("(deals_time_track.end_time > '".date('Y-m-d H:i:s',time())."' OR deals_time_track.end_time = '0000-00-00 00:00:00')" );    // ignore time while testing since deals are limited
                    $this->db->where("deals_time_track.start_time <= '".date('Y-m-d H:i:s',time())."'");                    
                }
            }
            // live search must be under end time
        }
        
   	// Skip deals with 0 left...
       	$this->db->where("deals.total != '' AND deals.total != '0'");

        /*if(isset($params['expired_time'])){
            $this->db->where("deals.end_time < ".$params['expired_time'] );
            $this->db->where("deals.status != 'draft'" );   // expired list does not count drafts
        }*/
        /*if(isset($params['expired_status'])){
            $this->db->or_where("deals.status = 'expired'" );
        }*/
        
        $this->db->join('stores','stores.id = deals.store_id','left');
        $this->db->join('locations','stores.location_id = locations.id','left');        
        $this->db->where("locations.city = ".$currentCity);
        $this->db->join('pages','pages.object_id = deals.id AND pages.type = "deal"','left');
        $this->db->join('user_meta','user_meta.meta_value = stores.id AND user_meta.meta_key = "store_id"','left');
        $this->db->join('users','users.id = user_meta.user_id','left');
        $this->db->where("users.status = '1'");    
        
        if(isset($params['limit_max']))
        {
            $this->db->limit($params['limit_max'], $params['limit_start']);
        }
        $this->db->group_by("deals.id");// Eliminate duplicate deals
        $this->db->order_by("deals.deal_order","asc");
        
        /*$r = $this->db->get()->result();
        echo "<pre>";
        print_r($r);
        echo "</pre>";
        exit();*/
        
        
        /*$this->db->get()->result();
        echo $this->db->last_query();
        exit();*/
        
        return $this->db->get()->result();
    }
        
    /**
     * Extract the deals with all the required information with the various input paramters given
     * =- search params
     * By category
     * -By locations
     * -price min
     * -price max
     * -discount min
     * 
     * =-verfiy
     * -location(NY)
     * -live deal
     * -store is active
     * -deal time is not up
     * -deal has started
     * 
     * =-return types
     * -deals + stores + page + ratings? + asset
     * -stores + page + ratings? + location? + asset
     * 
     * =-orderby
     * -deal_order
     * -today-start_time (newest)
     * -rating
     * -price high
     * -price low
     * 
     * =-limit
     * 
     * =- varation based on user preference...
     * 
     * 
     * @param array $params 
     */
    
    public function get_deals_extra($params = array()){
            
         $this->load->model('country_m'); 	
         $currentCity = $this->country_m->get_current_city();
                
                //$service = $params['service_types'];
                //$sortampm = $params['sortampm'];
    	  	//print_r($service);
                //echo "<pre>";
                //print_r($params);
                           
    	  if(isset($params['service_types']) && $params['service_types'] != "") {
    	  		$service_type = $params['service_types'];
                        
    	  		$cnt = 1;
    	  	}else{
    	  		$cnt = 0;
    	  		}
    	  
        $start_depth = 1; $total_depth = 3;
        $depth = $start_depth+$total_depth;
        $valid_keyword = (isset($params['keyword']) && $params['keyword'] && strlen($params['keyword']) > 3) ? true : false;
        $valid_term = (isset($params['term_id']) && $params['term_id']) ? true : false;
        
        $default = array(
            'return_type' => 'deal',
            'orderby' => 'deal_order',
            'limit_start' => 0,
            'limit_end' => 20
        );
                
        $params = array_merge($default,$params);
       /* echo "<pre>";
        print_r($params);*/
        // base select on what is being returned
        if($params['return_type'] == "deal")
        {
            $this->db->select("SQL_CALC_FOUND_ROWS deals.*,deals.id as deal_id,deals.name as deal_name, deals.description as deal_description, pages.slug, pages.parent_slug,locations.*,da.*, stores.*,t1.id as tid,t1.name as t1name,t2.name as t2name,t3.name as t3name,t4.name as t4name,deals_time_track.start_time,deals_time_track.end_time,deals_time_track.status,deals_time_track.deal_id ",false);
            $this->db->select("(SELECT AVG(ratings.rating) FROM ratings WHERE ratings.object_id = deal_id AND ratings.type = 'deal') AS rating_total");
            
            /**
            if(isset($params['term_id']) && $params['term_id']){
                //$this->db->from("terms as t$start_depth");
                //$this->db->where("t$start_depth.id",$params['term_id']);
                
                $this->db->from('deals');
            }else{
                $this->db->from('deals');
            }*/
            
            $this->db->from('deals');
           // $this->db->where('deals.type', 'basic');
           // $this->db->group_by(array("deal_id", "deals.type"));
            
           $this->db->group_by('deals.id');
        
        }elseif($params['return_type'] == 'store'){
            $this->db->select("SQL_CALC_FOUND_ROWS stores.*,storepage.slug, storepage.parent_slug,locations.address_1,locations.address_2,locations.city,locations.state,locations.zip,sa.filename,sa.ext,sa.location as flocation,deals_time_track.start_time,deals_time_track.end_time,deals_time_track.status,deals_time_track.deal_id ",false);
            $this->db->select("(SELECT AVG(ratings.rating) FROM ratings WHERE ratings.object_id = deals.id AND ratings.type = 'store') AS rating_total");
            
            /**
            if(isset($params['term_id']) && $params['term_id']){
                $this->db->from("terms as t$start_depth");
                $this->db->where("t$start_depth.id",$params['term_id']);
            }else{
                $this->db->from('deals');
            }
             * 
             */            
            $this->db->from('deals');
            //$this->db->distinct();
            $this->db->group_by('stores.id');
        } 
        elseif(isset($params['return_type']) && $params['return_type'] == 'countdown')
        {
            $this->db->select("SQL_CALC_FOUND_ROWS deals.*,deals.id as deal_id,deals.name as deal_name,HOUR(deals.start_time) as starttime,deals.description as deal_description, pages.slug, pages.parent_slug,locations.*,da.*, stores.*,t1.name as t1name,t2.name as t2name,t3.name as t3name,t4.name as t4name,deals_time_track.start_time,deals_time_track.end_time,deals_time_track.status,deals_time_track.deal_id ",false);
            $this->db->select("(SELECT AVG(ratings.rating) FROM ratings WHERE ratings.object_id = deal_id AND ratings.type = 'countdown') AS rating_total");
            $this->db->from('deals');
            $this->db->where('deals.type', 'countdown');            
            $this->db->group_by(array("deals.id", "deals.type"));
            if(isset($params['sortampm']) && $params['sortampm'] == 'ampm')
            {
                // $this->db->group_by('starttime');
                // $this->db->group_by(array("deals.id", "deals.type"));
            }
            else 
            {
                $this->db->group_by(array("deals.id", "deals.type"));
            }
            
            if(isset($params['timerange']) && !empty($params['timerange'])) 
            {
               $this->db->where("TIME_FORMAT(deals_time_track.start_time,'%k') <= '".$params['timerange']."'");
               $this->db->where("TIME_FORMAT(deals_time_track.end_time,'%k') >= '".$params['timerange']."'");
            }
            
            if(isset($params['search_date']) && !empty($params['search_date'])) 
            {
                //$this->db->where("deals_time_track.start_time <= '".date('Y-m-d H:i:s',strtotime($params['search_date']))."'");
                $this->db->where("date_format(date(deals_time_track.start_time),'%Y-%m-%d') = '".date('Y-m-d',strtotime($params['search_date']))."'");
            }
            
            if(isset($params['sortampm']) && $params['sortampm'] == 'ampm')
            {
                //$this->db->group_by('starttime');
                $this->db->order_by("starttime","ASC");
            }
        }
        elseif(isset($params['return_type']) && $params['return_type'] == 'staff_pick')
        {
            $this->db->select("SQL_CALC_FOUND_ROWS deals.*,deals.id as deal_id,deals.name as deal_name, deals.description as deal_description, pages.slug, pages.parent_slug,locations.*,da.*, stores.*,t1.name as t1name,t2.name as t2name,t3.name as t3name,t4.name as t4name,deals_time_track.start_time,deals_time_track.end_time,deals_time_track.status,deals_time_track.deal_id ",false);
            $this->db->select("(SELECT AVG(ratings.rating) FROM ratings WHERE ratings.object_id = deal_id AND ratings.type = 'countdown') AS rating_total");
            $this->db->from('deals');
            $this->db->where('deals.staffpick', '1');            
            $this->db->group_by('deals.id');
        }
                
        // Search the term relationship tree for category matching deals
        if($valid_term){
            $term_id = $params['term_id'];
        }else{
            $term_id = 0;
        }
        
        if(isset($params['return_type']) && $params['return_type'] == 'countdown' && isset($params['search_date']) && !empty($params['search_date']))
        {
            $this->db->join('deals_time_track','deals_time_track.deal_id = deals.id','left');    
        }
        /*elseif(isset($params['date_search']) && !empty($params['date_search']))
        {
            $this->db->join('deals_time_track','deals_time_track.deal_id = deals.id AND deals_time_track.status = "1"','left');    
        }*/
        else 
        {
            $this->db->join('deals_time_track','deals_time_track.deal_id = deals.id AND deals_time_track.status = "1"','left');    
        }
        
        $this->db->join('term_relationships as tr',"tr.object_id = deals.id AND tr.type = 'deal'","left");
        $this->db->join("terms as t$start_depth","t$start_depth.id = tr.term_id","left");
        
        $jstr = "";
        $j = 0;
        
        for($i = $start_depth; $i <= $depth; $i++){
            //$this->db->select("t$i.id as lev$i");
            $prev = $i-1;

            if($j!=0)
            {
                $this->db->join("terms as t$i","t$i.id = t$prev.parent","left");    // OR ids....
            }

            if($j!=0) $jstr .= " OR ";
            $jstr .= "t$i.id = ".$term_id;
            $j++;
        }
            
        if($term_id != 0){
            $this->db->where("(".$jstr.")");
        }
        
        //$this->db->join('term_relationships as tr',$jstr." AND tr.type = 'deal'","right");
        //$this->db->join("deals","tr.object_id = deals.id AND tr.type = 'deal'","left");
        
        /**
         *  maybe deprecated
         *$jstr = "";
            $j = 0;
            for($i = $start_depth; $i <= $depth; $i++){
                //$this->db->select("t$i.id as lev$i");
                $prev = $i-1;

                if($j!=0){
                    $this->db->join("terms as t$i","t$i.parent = t$prev.id","left");    // OR ids....
                }

                if($j!=0) $jstr .= " OR ";
                $jstr .= "tr.term_id = t$i.id";
                $j++;
            }
            $this->db->join('term_relationships as tr',$jstr." AND tr.type = 'deal'","right");
            //$this->db->join("deals","tr.object_id = deals.id AND tr.type = 'deal'","left");
         *  
         */
        
        //if trying to match a keyword
        if($valid_keyword){
            
            //entire keyword field needs to be a part of an OR statement
            //unless term id is set? then it needs to be more particular

            $h = 0;
            $hstr = '';
            
            $hstr .= " deals.name LIKE '%".$params['keyword']."%' OR ";
            $hstr .= " stores.name LIKE '%".$params['keyword']."%' OR ";
            
            //Search zip
            if(ctype_digit($params['keyword'])) {
            	$hstr .= " locations.zip = ".$params['keyword']." OR ";
            }
            
            for($i = $start_depth; $i <= $depth; $i++){
                //if($h!=0 && !$valid_term) $hstr .= " OR ";
                if($h!=0) $hstr .= " OR ";
                $hstr .= " t$i.name LIKE '%".$params['keyword']."%' ";
                $h++;
            }
            
            $this->db->where("(".$hstr.")");
            
            /* if(isset($params['term_id']) && $params['term_id']){
                
            }else{
                // search all the parents terms names for a like match
                
                $this->db->join('term_relationships as tr',"tr.object_id = deals.id AND tr.type = 'deal'","left");
                
                $this->db->join("terms as t$start_depth","t$start_depth.id = tr.term_id","left");
                $this->db->or_like("t$start_depth.name",$params['keyword']);
                $j = 0;
                for($i = $start_depth; $i <= $depth; $i++){
                    //$this->db->select("t$i.id as lev$i");
                    $prev = $i-1;

                    if($j!=0){
                        $this->db->join("terms as t$i","t$i.id = t$prev.parent","left");    // OR ids....
                    }

                    $this->db->or_like("t$i.name",$params['keyword']);
                    $j++;
                }

            }*/
        }
        
        // verify the deal is allowed to be shown
        $this->db->where('stores.status != "suspended"');   // suspended stores cant show deals
        if($cnt == 1){ 
        		foreach($service_type as $val){
					$vari[] =  $val;        		
        		}
        		$ids = implode(',',$vari);
				$where = "t1.id IN (".$ids.")";
				$this->db->where($where);
			}
        if(isset($params['city']) && !empty($params['city'])) {
        	$this->db->where('locations.city = "'.$params['city'].'"'); // search deals within the city we are at
        }
        
        $this->db->where('deals.status != "draft" AND deals.status != "expired"'); //Draft and expired deals cant be shown        
        
        // make sure the deal is live and in sellable hours
        $this->db->where("deals.status = 'live'" );
	
	// Skip deals with 0 left...
       	$this->db->where("deals.total != '' AND deals.total != '0'");

        //below code will show only currently ongoing deals
        $this->db->where("(deals_time_track.end_time > '".date('Y-m-d H:i:s',time())."' OR deals_time_track.end_time = '0000-00-00 00:00:00')" );    // ignore time while testing since deals are limited
        
        if(isset($params['return_type']) && $params['return_type'] != 'countdown')
        {
            $this->db->where("deals_time_track.start_time <= '".date('Y-m-d H:i:s',time())."'");
        }
        
        if(isset($params['latestdeal']) && $params['latestdeal'] == 'newest') 
        {
            $todayMidnight = mktime(0, 0, 0, date('n'), date('j'));        	
            $this->db->where("deals_time_track.start_time > '".date('Y-m-d H:i:s',$todayMidnight)."'");        	
        }
                
        $this->db->join('stores','stores.id = deals.store_id','left');
        $this->db->join('pages','pages.object_id = deals.id AND pages.type = "deal"','left');
        $this->db->join('pages as storepage','storepage.object_id = stores.id AND storepage.type = "store"','left');
        $this->db->join('locations','stores.location_id = locations.id','left');        
        $this->db->join('asset_relationships as dar','dar.object_id = deals.id AND dar.object_type = "deal_image"','left');
        $this->db->join('asset_relationships as sar','sar.object_id = stores.id AND sar.object_type = "store_image"','left');
        $this->db->join('assets as da','da.id = dar.asset_id','left');
        $this->db->join('assets as sa','sa.id = sar.asset_id','left');   
        $this->db->join('user_meta','user_meta.meta_value = stores.id AND user_meta.meta_key = "store_id"','left');
        $this->db->join('users','users.id = user_meta.user_id','left');
        $this->db->where("users.status = '1'");    
                
        // add search parameters               
        if(isset($params['location_id']) && $params['location_id']){
            //$this->db->join("term_relationships as ttr","ttr.object_id = stores.id AND ttr.type = 'store'","left");            
            $this->db->where(array("locations.state"=>$params['location_id']) );
        } else {            
            $this->db->where("locations.city = ".$currentCity);
        }
        
        if(isset($params['price_min']) && $params['price_min']){
            $this->db->where("deals.discount_price >= ".$params['price_min'] );
        }
        
        if(isset($params['price_max']) && $params['price_max']){
            $this->db->where("deals.discount_price <= ".$params['price_max'] );
        }
        
        if(isset($params['discount']) && $params['discount'])
        {
            $this->db->where("(100-((deals.discount_price/deals.original_price)*100)) >= ".$params['discount'] );
        }
        
        if(isset($params['store_id']) && $params['store_id'])
        {
            $this->db->where("stores.id", $params['store_id']);
        }       
                
        $this->db->limit($params['limit_end'], $params['limit_start']);
        
        switch($params['orderby']){
            case 'newest': $this->db->order_by("deals_time_track.start_time - '".date('Y-m-d H:i:s',time())."'","asc"); break;
            case 'rating': $this->db->order_by("rating_total","desc"); break;
            case 'pricehigh': $this->db->order_by("deals.discount_price","desc"); break;
            case 'pricelow': $this->db->order_by("deals.discount_price","asc"); break;
            case 'deal_order': 
            case 'featured': 
            default: $this->db->order_by("deals.featured","asc"); break;
        }
        
        $this->db->group_by("deals.id");// Eliminate duplicate deals
        $this->db->order_by("deals.name,stores.name","asc");

        $q = $this->db->get();

        //echo $this->db->last_query();
        //exit();
        
        $query = $this->db->query("SELECT FOUND_ROWS() AS 'total'");
        $total = $query->row()->total;

        $results = array(
            'results' => $q->result(),
            'total' => $total
        );
        
        return $results;        
    }

	/* deal according to service type starts*/
    
	public function get_deals_extra_service_type($params = array())
        {
            
         $this->load->model('country_m'); 	
         $currentCity = $this->country_m->get_current_city();
         
	  //$store_id = $params['search_params']['a_store_id'];
	  $store_id = $params['store_id'];
	  $page_name = $params['page_name'];
	  
	  if(empty($params['service_types']))
	  {
	 	$cnt = 0;
	  }else {
	  	$cnt = count($params['service_types']);
	  }
		 
	$start_depth = 1; $total_depth = 3;
        $depth = $start_depth+$total_depth;
        $valid_keyword = (isset($params['keyword']) && $params['keyword'] && strlen($params['keyword']) > 3) ? true : false;
        $valid_term = (isset($params['term_id']) && $params['term_id']) ? true : false;
        
        $default = array(
            'return_type' => 'deal',
            'orderby' => 'deal_order',
            'limit_start' => 0,
            'limit_end' => 20
        );
        
        $params = array_merge($default,$params);
        
        // base select on what is being returned
        if($params['return_type'] == "deal"){
            $this->db->select("SQL_CALC_FOUND_ROWS deals.*,deals.id as deal_id,deals.name as deal_name, deals.description as deal_description, pages.slug, pages.parent_slug,locations.*,da.*, stores.*,t1.id as tid,t1.name as t1name,t2.name as t2name,t3.name as t3name,t4.name as t4name,deals_time_track.start_time,deals_time_track.end_time,deals_time_track.status,deals_time_track.deal_id ",false);
            $this->db->select("(SELECT AVG(ratings.rating) FROM ratings WHERE ratings.object_id = deal_id AND ratings.type = 'deal') AS rating_total");

            /**
            if(isset($params['term_id']) && $params['term_id']){
                //$this->db->from("terms as t$start_depth");
                //$this->db->where("t$start_depth.id",$params['term_id']);
                
                $this->db->from('deals');
            }else{
                $this->db->from('deals');
            }*/
            
            $this->db->from('deals');
           // $this->db->where('deals.type', 'basic');
           // $this->db->group_by(array("deal_id", "deals.type"));
            
           $this->db->group_by('deals.id');
        
        }elseif($params['return_type'] == 'store'){
            $this->db->select("SQL_CALC_FOUND_ROWS stores.*, storepage.slug, storepage.parent_slug,locations.address_1,locations.address_2,locations.city,locations.state,locations.zip,sa.filename,sa.ext,sa.location as flocation,deals_time_track.start_time,deals_time_track.end_time,deals_time_track.status,deals_time_track.deal_id ",false);
            $this->db->select("(SELECT AVG(ratings.rating) FROM ratings WHERE ratings.object_id = deals.id AND ratings.type = 'store') AS rating_total");
            
            /**
            if(isset($params['term_id']) && $params['term_id']){
                $this->db->from("terms as t$start_depth");
                $this->db->where("t$start_depth.id",$params['term_id']);
            }else{
                $this->db->from('deals');
            }
             * 
             */
            
            $this->db->from('deals');
            //$this->db->distinct();
            $this->db->group_by('stores.id');
        } elseif(isset($params['return_type']) && $params['return_type'] == 'countdown'){
            $this->db->select("SQL_CALC_FOUND_ROWS deals.*,deals.id as deal_id,deals.name as deal_name, deals.description as deal_description, pages.slug, pages.parent_slug,locations.*,da.*, stores.*,t1.id as tid, t1.name as t1name,t2.name as t2name,t3.name as t3name,t4.name as t4name,deals_time_track.start_time,deals_time_track.end_time,deals_time_track.status,deals_time_track.deal_id ",false);
            $this->db->select("(SELECT AVG(ratings.rating) FROM ratings WHERE ratings.object_id = deal_id AND ratings.type = 'countdown') AS rating_total");
            $this->db->from('deals');
            $this->db->where('deals.type', 'countdown');            
            $this->db->group_by(array("deals.id", "deals.type"));
            
            if(isset($params['timerange']) && !empty($params['timerange'])) {
            	$this->db->where("date_format(date(deals_time_track.start_time),'%H') <= ".$params['timerange']);
            }
            if(isset($params['search_date']) && !empty($params['search_date'])) {
            	//$this->db->where("deals_time_track.start_time <= '".date('Y-m-d H:i:s',strtotime($params['search_date']))."'");
                $this->db->where("date_format(date(deals_time_track.start_time),'%Y-%m-%d') = '".date('Y-m-d',strtotime($params['search_date']))."'");
                //$this->db->where("date_format(date(deals_time_track.start_time),'%Y-%m-%d') <= ".$params['search_date']);
            }
            
        }
        
        // Search the term relationship tree for category matching deals
        
        if($valid_term)
        {
            $term_id = $params['term_id'];
        }
        else
        {
            $term_id = 0;
        }
        
        $this->db->join('deals_time_track','deals_time_track.deal_id = deals.id AND deals_time_track.status = "1"','left');    
        $this->db->join('term_relationships as tr',"tr.object_id = deals.id AND tr.type = 'deal'","left");
        $this->db->join("terms as t$start_depth","t$start_depth.id = tr.term_id","left");

        $jstr = "";
        
        $j = 0;
        
        for($i = $start_depth; $i <= $depth; $i++)
        {
            //$this->db->select("t$i.id as lev$i");
            $prev = $i-1;

            if($j!=0)
            {
                $this->db->join("terms as t$i","t$i.id = t$prev.parent","left");    // OR ids....
            }

            if($j!=0) $jstr .= " OR ";
            $jstr .= "t$i.id = ".$term_id;
            $j++;
        }
            
        if($term_id != 0)
        {
           $this->db->where("(".$jstr.")");
        }
            //$this->db->join('term_relationships as tr',$jstr." AND tr.type = 'deal'","right");
            //$this->db->join("deals","tr.object_id = deals.id AND tr.type = 'deal'","left");
        
        
        /**
         *  maybe deprecated
         *$jstr = "";
            $j = 0;
            for($i = $start_depth; $i <= $depth; $i++){
                //$this->db->select("t$i.id as lev$i");
                $prev = $i-1;

                if($j!=0){
                    $this->db->join("terms as t$i","t$i.parent = t$prev.id","left");    // OR ids....
                }

                if($j!=0) $jstr .= " OR ";
                $jstr .= "tr.term_id = t$i.id";
                $j++;
            }
            $this->db->join('term_relationships as tr',$jstr." AND tr.type = 'deal'","right");
            //$this->db->join("deals","tr.object_id = deals.id AND tr.type = 'deal'","left");
         *  
         */
        
        //if trying to match a keyword
        if($valid_keyword){
            
            //entire keyword field needs to be a part of an OR statement
            //unless term id is set? then it needs to be more particular
            
            $h = 0;
            $hstr = '';
            
            $hstr .= " deals.name LIKE '%".$params['keyword']."%' OR ";
            $hstr .= " stores.name LIKE '%".$params['keyword']."%' OR ";
            
            //Search zip
            if(ctype_digit($params['keyword'])) {
            	$hstr .= " locations.zip = ".$params['keyword']." OR ";
            }
            
            for($i = $start_depth; $i <= $depth; $i++){
                //if($h!=0 && !$valid_term) $hstr .= " OR ";
                if($h!=0) $hstr .= " OR ";
                $hstr .= " t$i.name LIKE '%".$params['keyword']."%' ";
                $h++;
            }
            
            $this->db->where("(".$hstr.")");
            
            
            /*
            if(isset($params['term_id']) && $params['term_id']){
                
                
                
                
            }else{
                // search all the parents terms names for a like match
                
                $this->db->join('term_relationships as tr',"tr.object_id = deals.id AND tr.type = 'deal'","left");
                
                $this->db->join("terms as t$start_depth","t$start_depth.id = tr.term_id","left");
                $this->db->or_like("t$start_depth.name",$params['keyword']);
                $j = 0;
                for($i = $start_depth; $i <= $depth; $i++){
                    //$this->db->select("t$i.id as lev$i");
                    $prev = $i-1;

                    if($j!=0){
                        $this->db->join("terms as t$i","t$i.id = t$prev.parent","left");    // OR ids....
                    }

                    $this->db->or_like("t$i.name",$params['keyword']);
                    $j++;
                }

            }
             * 
             */
            
            
        }
                
        //   echo "cnt".$cnt;
        // verify the deal is allowed to be shown
        $this->db->where('stores.status != "suspended"');
        
        if($cnt > 0)
        { 
            foreach($params['service_types'] as $val)
            {
              $vari[] =  $val;        		
            }
              $ids = implode(',',$vari);
              $where = "t1.id IN (".$ids.")";
              $this->db->where($where);
	}
        	
        	
        //$this->db->where('t1.id ='.$vari);
        // suspended stores cant show deals
        
        if(isset($params['city']) && !empty($params['city'])) 
        {
            $this->db->where('locations.city = "'.$params['city'].'"'); // search deals within the city we are at
        }
         
        //Draft and expired deals cant be shown           
	// Skip deals with 0 left...
        
       	$this->db->where("deals.total != '' AND deals.total != '0'");
      
        // make sure the deal is live and in sellable hours
         
        //below code will show only currently ongoing deals
        if($page_name == 'vendor'){
        $this->db->where("(deals_time_track.end_time > '".date('Y-m-d H:i:s',time())."' OR deals_time_track.end_time = '0000-00-00 00:00:00')" );    // ignore time while testing since deals are limited
        $this->db->where("deals_time_track.start_time <= '".date('Y-m-d H:i:s',time())."'");
        
        	if(isset($params['latestdeal']) && $params['latestdeal'] == 'newest') {
	        	$todayMidnight = mktime(0, 0, 0, date('n'), date('j'));        	
   	     	$this->db->where("deals_time_track.start_time > '".date('Y-m-d H:i:s',$todayMidnight)."'");        	
      	  }
      	$this->db->where("deals.status = 'live'" ); 

      	$this->db->where('deals.status != "draft" AND deals.status != "expired"'); 
        }
        
        else if($page_name == 'live-deals'){
        	$this->db->where("deals.status = 'live'" );
        	$this->db->where('deals.status != "draft" AND deals.status != "expired"');
        	}
        else if($page_name == 'draft-deals'){
        		$this->db->where("deals.status = 'draft'" );
        	}
        	else if($page_name == 'expired-deals'){
        		$this->db->where("deals.status = 'expired'" );
			}
        $this->db->join('stores','stores.id = deals.store_id','left');
        $this->db->join('pages','pages.object_id = deals.id AND pages.type = "deal"','left');
        $this->db->join('pages as storepage','storepage.object_id = stores.id AND storepage.type = "store"','left');
        $this->db->join('locations','stores.location_id = locations.id','left');
        $this->db->join('asset_relationships as dar','dar.object_id = deals.id AND dar.object_type = "deal_image"','left');
        $this->db->join('asset_relationships as sar','sar.object_id = stores.id AND sar.object_type = "store_image"','left');
        $this->db->join('assets as da','da.id = dar.asset_id','left');
        $this->db->join('assets as sa','sa.id = sar.asset_id','left');        
        
        
        // add search parameters        
        if(isset($params['location_id']) && $params['location_id'])
        {
            //$this->db->join("term_relationships as ttr","ttr.object_id = stores.id AND ttr.type = 'store'","left");            
            $this->db->where(array("locations.state"=>$params['location_id']) );
        } else {
            $this->db->where("locations.city = ".$currentCity);
        }
        
        if(isset($params['price_min']) && $params['price_min']){
            $this->db->where("deals.discount_price >= ".$params['price_min'] );
        }
        
        if(isset($params['price_max']) && $params['price_max']){
            $this->db->where("deals.discount_price <= ".$params['price_max'] );
        }
        
        if(isset($params['discount']) && $params['discount']){
            $this->db->where("(100-((deals.discount_price/deals.original_price)*100)) >= ".$params['discount'] );
        }
        
        /*if(isset($params['search_params']['a_store_id']) && $params['search_params']['a_store_id']){
            $this->db->where("stores.id", $params['search_params']['a_store_id']);
            
        }*/
	
        if(isset($params['store_id'])){
            $this->db->where("stores.id", $params['store_id']);
        }        
                
        $this->db->limit($params['limit_end'], $params['limit_start']);

        $this->db->group_by("deals.id");// Eliminate duplicate deals
        
        switch($params['orderby'])
        {
            case 'newest': $this->db->order_by("deals_time_track.start_time - '".date('Y-m-d H:i:s',time())."'","asc"); break;
            case 'rating': $this->db->order_by("rating_total","desc"); break;
            case 'pricehigh': $this->db->order_by("deals.discount_price","desc"); break;
            case 'pricelow': $this->db->order_by("deals.discount_price","asc"); break;
            case 'deal_order': case 'featured': default: $this->db->order_by("deals.deal_order","asc"); break;
        }
        
        $q = $this->db->get();        
        /*echo $this->db->last_query();
        exit();*/
        
        $query = $this->db->query("SELECT FOUND_ROWS() AS 'total'");
        $total = $query->row()->total;

        $results = array(
            'results' => $q->result(),
            'total' => $total
        );
        
        return $results;
    }
    
    public function get_wishlist_deals($limit = 5){
        
    	$this->db->select("deals.*,deals.id as deal_id,deals.name as deal_name, deals.description as deal_description,deals.start_time as deals_original_start_time,deals.end_time as deals_original_end_time,deals_time_track.*, pages.slug, pages.parent_slug, stores.* ");
    	$this->db->from('deals');
    	$this->db->where("deals.type = 'basic'");
        $this->db->join('deals_time_track','deals_time_track.deal_id = deals.id AND deals_time_track.status = "1"','left');
    	$this->db->join('stores','stores.id = deals.store_id','left');
    	$this->db->join('pages','pages.object_id = deals.id AND pages.type = "deal"','left');
    	$this->db->limit($limit);
        // make sure the deal is live and in sellable hours
        $this->db->where("deals.status = 'live'" );        
	// Skip deals with 0 left...
       	$this->db->where("deals.total != '' AND deals.total != '0'");
        //below code will show only currently ongoing deals
        $this->db->where("(deals_time_track.end_time > '".date('Y-m-d H:i:s',time())."' OR deals_time_track.end_time = '0000-00-00 00:00:00')" );    // ignore time while testing since deals are limited
        $this->db->where("deals_time_track.start_time <= '".date('Y-m-d H:i:s',time())."'");
        
        $this->db->group_by("deals.id");// Eliminate duplicate deals
    	$this->db->order_by("deals_time_track.start_time","asc");
        
    	return $this->db->get()->result();
    }
    
    public function get_new_deals($store_id){
        
           $this->db->select('*'); 
           $this->db->where('store_id',$store_id);
           $this->db->where('timestamp',date("Y-m-d"));
           $query = $this->db->get("deals"); 
           $row = $query->num_rows();
           
           return $row;
    }
    
    public function deletewishlist($listval,$listnew){
    	
    	$data = array(
            'meta_value' => $listnew);
       /* $this->db->where('meta_value',$listval);
        $this->db->delete('user_meta');
        delete_cookie("pmf_wishlist");*/
    	 $this->db->where('meta_value',$listval);
         $this->db->update('user_meta',$data);
    }
    
    public function update_deal_quantity($deal_id,$total_new){
    	
    	 $data = array('total' => $total_new);
      
    	 $this->db->where('id',$deal_id);
         $this->db->update('deals',$data);
    }
    
    public function getlist($listval){
        
        $query = $this->db->where('meta_value',$listval)->get('user_meta');
        if($query->num_rows() > 0){
        		$ret = '1';
        		$val = '';	
        }else {
        		$ret = '0';
        		$val['qty']='0';
        		$val['total']='0';
        		$val['saved']='0';
        } 
        return $val;     
    }
    
    public function get_recommended_rated_deals($limit=5) {
        $this->load->model('country_m');
        $currentCity = $this->country_m->get_current_city();        
        $this->db->select("deals.*,deals.id as deal_id,deals.name as deal_name, deals.description as deal_description,deals.start_time as deals_original_start_time,deals.end_time as deals_original_end_time,deals_time_track.*, pages.slug, pages.parent_slug, stores.* ");
        $this->db->select("(SELECT AVG(ratings.rating) FROM ratings WHERE ratings.object_id = deal_id AND ratings.type = 'deal') AS rating_total");
    	$this->db->from('deals');    	
        $this->db->join('deals_time_track','deals_time_track.deal_id = deals.id AND deals_time_track.status = "1"','left');
    	$this->db->join('stores','stores.id = deals.store_id','left');
        $this->db->join('locations','stores.location_id = locations.id','left');        
        $this->db->where("locations.city = ".$currentCity);
    	$this->db->join('pages','pages.object_id = deals.id AND pages.type = "deal"','left');
        $this->db->join('user_meta','user_meta.meta_value = stores.id AND user_meta.meta_key = "store_id"','left');
        $this->db->join('users','users.id = user_meta.user_id','left');

    	// $this->db->limit($limit);
        // make sure the deal is live and in sellable hours
        $this->db->where("deals.status = 'live'" );
	// Skip deals with 0 left...
       	$this->db->where("deals.total != '' AND deals.total != '0'");
        
        $this->db->where("users.status = '1'");    
        //below code will show only currently ongoing deals
        $this->db->where("(deals_time_track.end_time > '".date('Y-m-d H:i:s',time())."' OR deals_time_track.end_time = '0000-00-00 00:00:00')" );    // ignore time while testing since deals are limited
        $this->db->where("deals_time_track.start_time <= '".date('Y-m-d H:i:s',time())."'");
        
        $this->db->group_by("deals.id");// Eliminate duplicate deals
    	$this->db->order_by("rating_total","desc");
    
    	return $this->db->get()->result();    
    }
    
    public function get_ongoing_vendor_deals($store_id,$manage = true) {
        $this->load->model('country_m'); 	
        $currentCity = $this->country_m->get_current_city();
        $this->db->select('deals.id');
        $this->db->from('deals');
        $this->db->join('deals_time_track','deals_time_track.deal_id = deals.id AND deals_time_track.status = "1"','left');
        $this->db->join('stores','stores.id = deals.store_id','left');
        $this->db->join('locations','stores.location_id = locations.id','left');
        $this->db->where("locations.city = ".$currentCity);
        $this->db->where('deals.store_id',$store_id);
        if($manage) {
            //below code will show only currently ongoing deals
            $this->db->where("(deals_time_track.end_time > '".date('Y-m-d H:i:s',time())."' OR deals_time_track.end_time = '0000-00-00 00:00:00')" );    // ignore time while testing since deals are limited
            $this->db->where("deals_time_track.start_time <= '".date('Y-m-d H:i:s',time())."'");
        }
        $query = $this->db->get();
        //echo $this->db->last_query();
        return $query->result();
    }
    
    public function get_vendor_service_type($store_id,$termList = array()){
    	 /*$this->db->select('deals.id as id,term_relationships.object_id as obj_id,terms.name as name');
    	 $this->db->from('deals'); 
    	 $this->db->join('term_relationships','deals.id = term_relationships.object_id','left');
    	 $this->db->join('terms','terms.id = term_relationships.object_id','left');
       $this->db->where('deals.store_id',$store_id);
       $this->db->group_by('deals.id');
       return $this->db->get()->result();*/                        
        
        $this->db->select('terms.id as tid, terms.name as t1name,t2.name as t2name,t2.id as t2id, t3.name as t3name,t3.id as t3id, t4.name as t4name,t4.id as t4id');
        $this->db->from('terms');                        
        $this->db->join('terms as t2','t2.id = terms.parent','left');
        $this->db->join('terms as t3','t3.id = t2.parent','left');
        $this->db->join('terms as t4','t4.id = t3.parent','left');
        $sql = '';
        foreach($termList as $termId) {
            if(empty($sql)) {
                $sql = ' terms.id = "'.$termId.'"';
            } else {
                $sql .= ' OR terms.id = "'.$termId.'"';
            }
        }        
        $this->db->where($sql,NULL,FALSE);        
        $this->db->order_by("t1name","desc");
        $this->db->group_by(array('tid','t1name','t2name','t3name','t4name'));
        $q = $this->db->get();       
        //echo $this->db->last_query();        
        
        return $q->result();
    }

    
     // Deals Time Track Cron Function Start
    
     public function get_time_track_deals(){
        
                    $this->db->select("deals.*");
                    $this->db->from('deals');        
        
                   return $this->db->get()->result();    
      }
      
      public function get_deal_total($deal_id) 
      {
         if($deal_id)
         {
            $this->db->select('total'); 
            $this->db->from("deals");
            $this->db->where('id',$deal_id);
            $query = $this->db->get(); 
            $row = $query->row('total');      
            
            if($row == "0") { return true; } else { return false; }
         }
      }
      public function has_time_track_deals($deal_id,$starttime,$endtime,$status){
        $time_track = array(
            'deal_id' => $deal_id,
            'start_time' => $starttime,
            'end_time' => $endtime,
            'status' => $status
        );
        $this->db->where($time_track);
        $res = $this->db->get('deals_time_track')->result();
        
        if(empty($res)){
            return false;
        }
        
        return true;
    }
      
    // Deals Time Track Cron Function End

		
	public function get_redeem_deal_data($deal_id) {
			$this->db->select('redeem_time'); 
			//$this->db->from('redeem_deals');
         $this->db->where('deal_id',$deal_id);
         $res = $this->db->get('redeem_deals')->row();
            
            return $res;
         //echo $this->db->last_query();
         //$que = $this->db->get();
         //echo $this->db->last_query();
         //return $que->result();
	}	    
    
        public function get_redeem_deal_voucher_data($deal_id,$voucherNo) 
        {	 
         
         $this->db->select('redeem_time'); 
	 //$this->db->from('redeem_deals');
         $this->db->where('deal_id',$deal_id);
         $this->db->where('voucher_no',$voucherNo);
         $res = $this->db->get('redeem_deals')->row();
         //echo $this->db->last_query();   
	 return $res;
         //$que = $this->db->get();
         //echo $this->db->last_query();
         //return $que->result();
         
	}
	
	public function get_redeem_deal_voucher_deal_row($deal_id,$voucherNo) 
        {	
            $this->db->select('redeem_time'); 
            $this->db->from('redeem_deals');
            $this->db->where('deal_id',$deal_id);
            $this->db->where('voucher_no',$voucherNo);
            $total_row = $this->db->count_all_results();
            
            return $total_row;
	}
        
        public function insert_redeem_deal($data)
        {
    		$this->db->insert('redeem_deals',$data);
    		//echo $this->db->last_query();
                $id = $this->db->insert_id();
         
                return $id;  
    	}
	
        public function insert_deal_visit_log($data)
        {
    		$this->db->insert('deals_visit_log',$data);
    		//echo $this->db->last_query();
                $id = $this->db->insert_id();
         
                return $id;  
    	}
    	
        public function save_deals_extra($deal_data)
	{
            
            if(isset($deal_data) && is_array($deal_data) && !empty($deal_data))
            {

                foreach($deal_data as $row){
					
                    $this->save_deal_extra_deals($row);
                    $is_dup = $this->get_deals_extra_status($row['deal_id'],$row['extra']);
                    $last_status = $this->get_deals_extra_last_status($row['deal_id'],$row['extra']);
					
		    //$status = $last_status->status;
					
		    if($is_dup == '0' && $row['val'] == 'checked')
                    {					
                            $data = array(
                                        'deal_id' => $row['deal_id'],
                                        'extra' => $row['extra'],
                                        'status' => $row['val'],
                                        'created_time' => date('Y-m-d H:i:s',time())
                                    );
                            
                            $insert = $this->db->insert('deals_extra_log',$data);	
                            if($insert)
                            { return true; }
                    }else if($is_dup == '1' && $row['val'] == 'unchecked'){
                            $data = array(
                                    'deal_id' => $row['deal_id'],
                                    'extra' => $row['extra'],
                                    'status' => $row['val'],
                                    'created_time' => date('Y-m-d H:i:s',time())
                                    );

                           $insert = $this->db->insert('deals_extra_log',$data);
                           if($insert)
                           { return true; }
                           
                            }			
                       }
                    }
	 }
		
	 public function save_deal_extra_deals($row)
         {
				if($row['val'] == 'checked'){
						if($row['extra'] == 'Featured'){
							$values = array(
            				'featured' => '1');
			    			$this->db->where('id',$row['deal_id']);
         				$this->db->update('deals',$values);
         			}else{
         				$values = array(
            				'staffpick' => '1');
			    			$this->db->where('id',$row['deal_id']);
         				$this->db->update('deals',$values);	
						}		
					}else{
						if($row['extra'] == 'Featured'){
							$values = array(
            				'featured' => '0');
			    			$this->db->where('id',$row['deal_id']);
         				$this->db->update('deals',$values);
         			}else{
         				$values = array(
            				'staffpick' => '0');
			    			$this->db->where('id',$row['deal_id']);
         				$this->db->update('deals',$values);	
						}
					}
		}   	
	 public function get_deals_extra_status($deal_id,$extra){
				$this->db->select('*');
				$this->db->from('deals_extra_log');
				$this->db->where('deal_id',$deal_id);
        		$this->db->where('extra',$extra);
        		$this->db->where('status','checked');
				$total_row = $this->db->count_all_results();
				return $total_row;

		}
           
          public function get_deals_extra_last_status($deal_id,$extra){
                            $this->db->select('status');
                            $this->db->where('deal_id',$deal_id);
                    $this->db->where('extra',$extra);
                    $this->db->order_by('id','desc');
                    $this->db->limit('1');
                    $res = $res = $this->db->get('deals_extra_log')->row();
                    //echo $this->db->last_query();
                            return $res;
            }		
	
         public function get_deal_name_by_id($deal_id){
                            $this->db->select('name');
                            $this->db->where('id',$deal_id);
                            $res = $res = $this->db->get('deals')->row();
                    //echo $this->db->last_query();
                            return $res;
            }
            
         public function get_vendor_deal_create($month_filter="",$year_filter="",$week_filter="",$day_filter = "")
          {
        
            $this->db->select("deals.id as deal_id,Count(deals.id) as deal_count,deals.name as deal_name, stores.id as stores_id");
            $this->db->from('deals');
            
            if($month_filter) { $this->db->where("date_format(date(timestamp),'%m') = '".$month_filter."'"); }

            if($week_filter) 
            {            
                $weekDates = $this->report->getStartAndEndDateofWeek($week_filter, $month_filter, $year_filter);
                $this->db->where("date_format(date(timestamp),'%d') >=", $weekDates[0]);
                $this->db->where("date_format(date(timestamp),'%d') <=", $weekDates[1]);
            }

            if($day_filter) { $this->db->where("date_format(date(timestamp),'%a') = '".$day_filter."'"); }

            if($year_filter) { $this->db->where("date_format(date(timestamp),'%Y') = '".$year_filter."'"); }
            
            $this->db->join('stores','stores.id = deals.store_id','left');
            $this->db->group_by('stores.id');
            $this->db->order_by("deals.deal_order","asc");

            return $this->db->get()->result();
          }
        
        public function get_vendor_deal_redeemed($month_filter="",$year_filter="",$week_filter="",$day_filter = "")
        {
            $this->db->select("redeem_deals.deal_id as redeem_id,count(redeem_deals.redeem_deal_id) as redeem_count,s.id as stores_id");
            $this->db->from('stores as s,redeem_deals');
            
            if($month_filter) { $this->db->where("date_format(date(redeem_time),'%m') = '".$month_filter."'"); }

            if($week_filter) 
            {            
                $weekDates = $this->report->getStartAndEndDateofWeek($week_filter, $month_filter, $year_filter);
                $this->db->where("date_format(date(redeem_time),'%d') >=", $weekDates[0]);
                $this->db->where("date_format(date(redeem_time),'%d') <=", $weekDates[1]);
            }

            if($day_filter) { $this->db->where("date_format(date(redeem_time),'%a') = '".$day_filter."'"); }

            if($year_filter) { $this->db->where("date_format(date(redeem_time),'%Y') = '".$year_filter."'"); } 
            
            
            $this->db->join('deals','deals.id = redeem_deals.deal_id','left');
            $this->db->join('stores','s.id = deals.store_id','left');
            $this->db->group_by('s.id');
            //$this->db->get()->result();
            //echo $this->db->last_query();
            return $this->db->get()->result();
        }
        
        public function get_pay_vendor_deal_redeemed($month_filter="",$year_filter="",$week_filter="",$day_filter = "")
        {
            //$this->db->select("redeem_deals.deal_id as redeem_id,count(redeem_deals.redeem_deal_id) as redeem_count,redeem_deals.redeem_time,s.id as stores_id");
            //$this->db->from('stores as s,redeem_deals');
            $this->db->select("redeem_deals.deal_id as redeem_id,count(redeem_deals.redeem_deal_id) as redeem_count,redeem_deals.redeem_time,stores.id as stores_id");
            $this->db->from('redeem_deals');
            $this->db->where("redeem_deals.deal_id != '0'");
            if($month_filter) { $this->db->where("date_format(date(redeem_time),'%m') = '".$month_filter."'"); }

            if($week_filter) 
            {            
                $weekDates = $this->report->getStartAndEndDateofWeek($week_filter, $month_filter, $year_filter);
                $this->db->where("date_format(date(redeem_time),'%d') >=", $weekDates[0]);
                $this->db->where("date_format(date(redeem_time),'%d') <=", $weekDates[1]);
            }

            if($day_filter) { $this->db->where("date_format(date(redeem_time),'%a') = '".$day_filter."'"); }

            if($year_filter) { $this->db->where("date_format(date(redeem_time),'%Y') = '".$year_filter."'"); } 
                        
            $this->db->join('deals','deals.id = redeem_deals.deal_id','left');
            $this->db->join('stores','stores.id = deals.store_id','left');
            $this->db->group_by('stores.id');
            // $this->db->get()->result();
            // echo $this->db->last_query();
            return $this->db->get()->result();
        }
        
        public function get_deal_log($deal_id,$user_id)
        {
            $this->db->select('*');
            $this->db->from('deals_visit_log');
            $this->db->where('deal_id',$deal_id);
            $this->db->where('user_id',$user_id);
            return $total_row = $this->db->count_all_results();            
        }
        
        // other that bought Start By Iflair On 27 may 2013
        
        public function get_sales_history($user_id,$deal_id)
        {   
            $this->db->select("deals.*,deals.id as deal_id,order_items.*, pages.slug, pages.parent_slug, stores.id as store_id");
            $this->db->from('deals,order_items');

            $this->db->where("deals.id = order_items.item_id");
            $this->db->where("order_items.item_id !=",$deal_id);

            // $this->db->join('order_items','order_items.item_id = deals.id','left');
            $this->db->join('stores','stores.id = deals.store_id','left');
            $this->db->join('pages','pages.object_id = deals.id AND pages.type = "deal"','left');
            $this->db->join('user_meta','user_meta.meta_value = stores.id AND user_meta.meta_key = "store_id"','left');
            $this->db->join('users','users.id = user_meta.user_id','left');
            // $this->db->where("deals.status = 'live'");
            $this->db->where_in("users.id",$user_id);
            $this->db->order_by("order_items.id","desc");      
            $this->db->group_by('order_items.item_id');
            return $this->db->get()->result();

        }        
        
        // other that bought End
        
        public function get_sitewide_wishlist($deal_type_filter="",$month_filter="",$year_filter="",$week_filter="",$day_filter = "") 
        {        
            $keya = 'wishlist';
            $keyb = 'wishlist_updated_date';            
            //SELECT uma.meta_value as wishlist, FROM_UNIXTIME(umb.meta_value) as updatedtime FROM `user_meta` as uma,`user_meta` as umb WHERE uma.`meta_key` = 'wishlist' and umb.`meta_key` = 'wishlist_updated_date' AND uma.`user_id` = umb.`user_id`
            $this->db->select('uma.meta_value as wishlist , FROM_UNIXTIME(umb.meta_value) as updated_date');
            $this->db->from('user_meta as uma,user_meta as umb');
            $this->db->where('uma.meta_key = "'.$keya.'"');
            $this->db->where('umb.meta_key = "'.$keyb.'"');
            $this->db->where('uma.user_id = umb.user_id');                        
            
            if($month_filter) {
                $this->db->where("date_format(date(FROM_UNIXTIME(umb.meta_value)),'%m') = '".$month_filter."'");
            }

            if($week_filter) {            
                $weekDates = $this->report->getStartAndEndDateofWeek($week_filter, $month_filter, $year_filter);
                $this->db->where("date_format(date(FROM_UNIXTIME(umb.meta_value)),'%d') >=", $weekDates[0]);
                $this->db->where("date_format(date(FROM_UNIXTIME(umb.meta_value)),'%d') <=", $weekDates[1]);
            }

            if($day_filter) {
                $this->db->where("date_format(date(FROM_UNIXTIME(umb.meta_value)),'%a') ", $day_filter);
            }

            if($year_filter) {
                $this->db->where("date_format(date(FROM_UNIXTIME(umb.meta_value)),'%Y') = '".$year_filter."'");             
            }
            $this->db->order_by('updated_date','desc');
            /*$this->db->get()->result();
            echo $this->db->last_query();*/
            return $this->db->get()->result();        
        }
        
        public function get_purchase_wishlist_favorite_history($deal_type_filter="",$month_filter="",$year_filter="",$week_filter="",$day_filter = "") {
            $this->load->library('report');            
            $output = array();
            
            //get purchase data starts
            $this->db->select("count(*) as Order_Count");            
            $this->db->select('order.process_date');
            // $this->db->select("deals.*, count(order_items.*) as Order_Count, order.*");
            $this->db->from('deals,order_items');
            $this->db->where("deals.id = order_items.item_id");
            if($deal_type_filter) {
                $this->db->join('term_relationships','term_relationships.object_id = deals.id AND term_relationships.type = "deal"','left');                
                $this->db->where("term_relationships.term_id = ".$deal_type_filter);
            }
            if($month_filter) {
                $this->db->where("date_format(date(process_date),'%m') = '".$month_filter."'");
            }
            
            if($week_filter) {            
                $weekDates = $this->report->getStartAndEndDateofWeek($week_filter, $month_filter, $year_filter);
                $this->db->where("date_format(date(process_date),'%d') >=", $weekDates[0]);
                $this->db->where("date_format(date(process_date),'%d') <=", $weekDates[1]);
            }

            if($day_filter) {
                $this->db->where("date_format(date(process_date),'%a') ", $day_filter);
            }

            if($year_filter) {
                $this->db->where("date_format(date(process_date),'%Y') = '".$year_filter."'");             
            }

            $this->db->join('order','order_items.order_id = order.id','left');            
            $this->db->join('pages','pages.object_id = deals.id AND pages.type = "deal"','left');            
            $this->db->order_by("order_items.id","desc");       

            /*$this->db->get()->result();
            echo $this->db->last_query();*/
            $purchaseHistoryResult = $this->db->get()->row();
            $output[0]['Order_Count'] = $purchaseHistoryResult->Order_Count;
            $output[0]['name'] = 'Purchase';
            //get purchase data ends
                        
            //get wishlist data starts
            $siteWideWishlist = $this->get_sitewide_wishlist($deal_type_filter,$month_filter,$year_filter,$week_filter,$day_filter);
            $totalWishlist = 0;            
            $existsDealList = array();
            if($deal_type_filter) {
                $this->db->select("deals.id");
                $this->db->from("deals");                                
                $this->db->join('term_relationships','term_relationships.object_id = deals.id AND term_relationships.type = "deal"','left');                
                $this->db->where("term_relationships.term_id = ".$deal_type_filter);
                $existsDealResult = $this->db->get()->result();
                foreach($existsDealResult as $dealId) {
                    $existsDealList[] = $dealId->id;
                }
            }             
            foreach($siteWideWishlist as $wishlist) {
                $wishlist = unserialize($wishlist->wishlist);                
                foreach($wishlist as $wishlistDealsKey => $wishlistDeals) {
                    if($deal_type_filter) {
                        if(in_array($wishlistDealsKey, $existsDealList)) {
                            $totalWishlist += $wishlistDeals['quantity'];
                        }
                    } else {
                        $totalWishlist += $wishlistDeals['quantity'];
                    }
                }                
            }                              
            $output[1]['Order_Count'] = $totalWishlist;
            $output[1]['name'] = 'Wishlist';
            //get wishlist data ends
                        
            //get favorite vendors data starts
            $this->db->select("count(*) as Order_Count");            
            $this->db->select('time_stamp');
            // $this->db->select("deals.*, count(order_items.*) as Order_Count, order.*");
            $this->db->from('myfav_vendors,stores');            
            $this->db->where('myfav_vendors.store_id = stores.id');
            
            if($deal_type_filter) {
                $this->db->join('deals','deals.store_id = stores.id','left');
                $this->db->join('term_relationships','term_relationships.object_id = deals.id AND term_relationships.type = "deal"','left');                
                $this->db->where("term_relationships.term_id = ".$deal_type_filter);
            }
            
            if($month_filter) {
                $this->db->where("date_format(date(time_stamp),'%m') = '".$month_filter."'");
            }
            
            if($week_filter) {            
                $weekDates = $this->report->getStartAndEndDateofWeek($week_filter, $month_filter, $year_filter);
                $this->db->where("date_format(date(time_stamp),'%d') >=", $weekDates[0]);
                $this->db->where("date_format(date(time_stamp),'%d') <=", $weekDates[1]);
            }

            if($day_filter) {
                $this->db->where("date_format(date(time_stamp),'%a') ", $day_filter);
            }

            if($year_filter) {
                $this->db->where("date_format(date(time_stamp),'%Y') = '".$year_filter."'");             
            }

            $this->db->order_by("time_stamp","desc");       

            /*$this->db->get()->result();
            echo $this->db->last_query();*/
            $favoriteVendorsResult = $this->db->get()->row();
            $output[2]['Order_Count'] = $favoriteVendorsResult->Order_Count;
            $output[2]['name'] = 'Favorite Vendors';
            //get favorite vendors data ends

            return $output;
        }
        
         /* Average Cost of Deal Type Start By Iflair on 29 may 2013 */
        
        public function get_average_cost_of_deals($user_id,$type_val)
        {        
            //$this->db->select('order_items.item_quantity');
            $this->db->select('order_items.item_price');
            
            $this->db->select("count(*) as Order_Count");            
            //$this->db->select("ROUND(order_items.item_price/count(*)) as Sold_Avg"); 
            //$this->db->select_avg("order_items.item_price");
            
            $this->db->select('deals.name');
            $this->db->select('deals.id');
            $this->db->select('deals.type');
            $this->db->from('deals,order_items');
            $this->db->where("deals.id = order_items.item_id");
            $this->db->where("deals.type",$type_val);   
            $this->db->join('order','order_items.order_id = order.id','left');
            $this->db->join('stores','stores.id = deals.store_id','left');
            $this->db->join('pages','pages.object_id = deals.id AND pages.type = "deal"','left');
            $this->db->join('user_meta','user_meta.meta_value = stores.id AND user_meta.meta_key = "store_id"','left');
            $this->db->join('users','users.id = user_meta.user_id','left');
            $this->db->where("users.id",$user_id);   
            $this->db->group_by("order_items.item_id");        
            $this->db->order_by("order_items.id","desc");       

            $data = $this->db->get()->result();
            $item_quantity = array();
            $item_price = array();
            $final_data = array();
            
            foreach($data as $val)
            {
                $item_quantity[] = $val->Order_Count;
                $item_price[] = $val->item_price;
            }

            $final_data['item_quantity'] = array_sum($item_quantity);
            $final_data['item_price'] = array_sum($item_price);
            if($final_data['item_price'] > 0)
            {
             $final_data['average'] = ceil($final_data['item_price']/$final_data['item_quantity']);
            }
            else 
            {
             $final_data['average'] = "0";   
            }
            $final_data['type'] = $type_val;
            
            return $final_data;
        }
        
     /* Average Cost of Deal Type End */
     
     /* Top selling deal types Report start by iflair on 29 may 2013  */
        
     public function get_vendor_sales_by_type($user_id,$type_filter="basic",$month_filter="",$year_filter="",$week_filter="",$day_filter = "")
     {        
        $this->load->library('report');
        $this->db->select("count(*) as Order_Count");
        $this->db->select('deals.name');
        $this->db->select('deals.type');
        $this->db->select('order.process_date');
        // $this->db->select("deals.*, count(order_items.*) as Order_Count, order.*");
	$this->db->from('deals,order_items');
        $this->db->where("deals.id = order_items.item_id");
        $this->db->where("deals.type",$type_filter);  
        
        if($month_filter) {
            $this->db->where("date_format(date(process_date),'%m') = '".$month_filter."'");
        }
        
        if($week_filter) {            
            $weekDates = $this->report->getStartAndEndDateofWeek($week_filter, $month_filter, $year_filter);
            $this->db->where("date_format(date(process_date),'%d') >=", $weekDates[0]);
            $this->db->where("date_format(date(process_date),'%d') <=", $weekDates[1]);
        }
        
        if($day_filter) {
            $this->db->where("date_format(date(process_date),'%a') ", $day_filter);
        }
        
        if($year_filter) {
            $this->db->where("date_format(date(process_date),'%Y') = '".$year_filter."'");             
        }
        
        $this->db->join('order','order_items.order_id = order.id','left');
        $this->db->join('stores','stores.id = deals.store_id','left');
        $this->db->join('pages','pages.object_id = deals.id AND pages.type = "deal"','left');
        $this->db->join('user_meta','user_meta.meta_value = stores.id AND user_meta.meta_key = "store_id"','left');
        $this->db->join('users','users.id = user_meta.user_id','left');
        // $this->db->where("deals.status = 'live'");       
        $this->db->where("users.id",$user_id);   
        $this->db->group_by("order_items.item_id");        
        $this->db->order_by("order_items.id","desc");       
        
        /*$this->db->get()->result();
        echo $this->db->last_query();*/
        
        $data = $this->db->get()->result();
        $item_quantity = array();
        
            foreach($data as $val)
            {
                $item_quantity[] = $val->Order_Count;
            }

            $final_data['Order_Count'] = array_sum($item_quantity);
            
            if(!$type_filter)
            {
                $final_data['type'] = "basic";
            }
            else 
            {
                $final_data['type'] = $type_filter;
            }
            return $final_data;
    }
    
     /* Top selling deal types Report End */
    
     public function get_avg_cost_number_of_deals($deal_type_filter="",$month_filter="",$year_filter="",$week_filter="",$day_filter="") {
         //$this->db->select('sum(order_items.item_price) as order_total,count(order_items.item_id) as order_counter,order.process_date');
         $this->db->select('AVG(order_items.item_price) as avg_cost,count(order_items.item_id) as order_counter,order.process_date');
         $this->db->from('order_items');
         $this->db->join('order','order_items.order_id = order.id','left');         
         if($deal_type_filter) {
            $this->db->join('deals','deals.id = order_items.id','left');
            $this->db->join('term_relationships','term_relationships.object_id = deals.id AND term_relationships.type = "deal"','left');                
            $this->db->where("term_relationships.term_id = ".$deal_type_filter);
        }
         
         if($month_filter) {
            $this->db->where("date_format(date(process_date),'%m') = '".$month_filter."'");
        }
        
        if($week_filter) {            
            $weekDates = $this->report->getStartAndEndDateofWeek($week_filter, $month_filter, $year_filter);
            $this->db->where("date_format(date(process_date),'%d') >=", $weekDates[0]);
            $this->db->where("date_format(date(process_date),'%d') <=", $weekDates[1]);
        }
        
        if($day_filter) {
            $this->db->where("date_format(date(process_date),'%a') ", $day_filter);
        }
        
        if($year_filter) {
            $this->db->where("date_format(date(process_date),'%Y') = '".$year_filter."'");             
        }
        $this->db->order_by("order_items.id","desc");
        $this->db->group_by("order_items.order_id"); 
        /*$this->db->get()->result();
        echo $this->db->last_query();*/
        return $this->db->get()->result();
     }
    
    /* Vendor Store Visitor Report start by iflair on 30 may 2013  */
        
    public function get_vendor_visitor($object_id,$object_type,$month_filter="",$year_filter="",$week_filter="",$day_filter = "")
    {   
        $this->load->library('report');
        $this->db->select('count(*) as visit_count,users.role');        
        $this->db->from('deals_visit_log');
        $this->db->where('object_id',$object_id);
        $this->db->where('oject_type',$object_type);        
                
        if($month_filter) 
        {
            $this->db->where("date_format(date(visited_time),'%m') = '".$month_filter."'");
        }
        
        if($week_filter) 
        {            
            $weekDates = $this->report->getStartAndEndDateofWeek($week_filter, $month_filter, $year_filter);
            $this->db->where("date_format(date(visited_time),'%d') >=", $weekDates[0]);
            $this->db->where("date_format(date(visited_time),'%d') <=", $weekDates[1]);
        }
        
        if($day_filter) 
        {
            $this->db->where("date_format(date(visited_time),'%a') ", $day_filter);
        }
        
        if($year_filter) 
        {
            $this->db->where("date_format(date(visited_time),'%Y') = '".$year_filter."'");             
        }
        
        $this->db->join('users','users.id = deals_visit_log.user_id','left');
        $this->db->where("users.role != 'admin'");
        $this->db->group_by("users.role"); 
               
        $result = $this->db->get()->result();
               
       return $result;       
    }
    
    /* Vendor Store Visitor Report End */    
    
    /* Users Spent Time at Store Start by iflair on 30 may 2013  */
        
    public function get_store_time_spent($object_ids,$object_type,$user_id,$month_filter="",$year_filter="",$week_filter="",$day_filter = "")
    {        
        $this->load->library('report');
        $this->db->select('deals_visit_log.*,users.role,users.firstname,deals.name');
        
        //$this->db->select('AVG(visited_time) as time_avg');
        
        //$this->db->select('count(*) as time_count');
        
        // $this->db->select('AVG(max(visited_time) - min(visited_time) as time_visit)');
        // $this->db->select('MAX(visited_time) as max_visited');
        // $this->db->select('MIN(visited_time) as min_visited');
        // $this->db->select('max(visited_time) - min(visited_time) as time_visit');
        
        $this->db->from('deals_visit_log');
        
        $where = "object_id IN (".$object_ids.")";
	$this->db->where($where);
        $this->db->where('oject_type',$object_type);        
                
        if($month_filter) 
        {
            $this->db->where("date_format(date(visited_time),'%m') = '".$month_filter."'");
        }
        
        if($week_filter) 
        {            
            $weekDates = $this->report->getStartAndEndDateofWeek($week_filter, $month_filter, $year_filter);
            $this->db->where("date_format(date(visited_time),'%d') >=", $weekDates[0]);
            $this->db->where("date_format(date(visited_time),'%d') <=", $weekDates[1]);
        }
        
        if($day_filter) 
        {
            $this->db->where("date_format(date(visited_time),'%a') ", $day_filter);
        }
        
        if($year_filter) 
        {
            $this->db->where("date_format(date(visited_time),'%Y') = '".$year_filter."'");             
        }
        
        $this->db->join('users','users.id = deals_visit_log.user_id','left');
        // $this->db->join('deals','deals.store_id = deals_visit_log.object_id','left');
        $this->db->join('deals','deals.id = deals_visit_log.object_id','left');
        $this->db->where("users.role != 'admin'"); 
        $this->db->where("users.id != ",$user_id); 
        
        //$this->db->group_by("deals_visit_log.object_id"); 
        
        // $this->db->group_by("MONTH(visited_time)"); 
        
        //$this->db->group_by("YEAR(visited_time)"); 
        //$this->db->group_by("DAY(visited_time)"); 
        //$this->db->group_by("deals_visit_log.user_id"); 
        
        $this->db->order_by("deals_visit_log.id","ASC");        
                
        /*$this->db->get()->result();
        echo $this->db->last_query();
        exit();*/
        
        $result_data = $this->db->get()->result();
        
              // echo "<pre>";
              // print_r($result_data);
        
              /*  $visited_time = array();
                $time_count = array();
                $user_ids = array();
                $time_spent = array();
                $result_data  = array();
                $dealCurrentTime = "";
                    echo "Visited Time : ";            
                foreach($result_data as $key => $val)
                {
                    //$visited_time[] = $val->visited_time;
                    //$time_count[] = $val->time_count;
                    //$user_ids[] = $val->user_id; 
                    echo $key;
                    echo "<br />";
                    echo "Visited Time : ".$time_val = $val['visited_time'];
                                        
                    $diff = abs(strtotime($val->visited_time) - strtotime($dealCurrentTime));   
                                        
                    $minutes = round($diff / 60 % 60);
                    
                    //$time_spent[] = $minutes;
                    $val->time_spent = $minutes;
                    
                    $dealCurrentTime = $time_val;  
                }
                
                /*foreach($visited_time as $time)
                {
                    $time_val = $time;
                                        
                    $diff = abs(strtotime($time) - strtotime($dealCurrentTime));   
                                        
                    $minutes = round($diff / 60 % 60);
                    
                    $time_spent[] = $minutes;
                    $dealCurrentTime = $time_val;                    
                }*/
                
                //$user_count = count($user_ids);
                //$data['visited_time'] = $visited_time;
                //$total_time_spent = array_sum($time_spent);
                //$data['time_spent_avg'] = $total_time_spent/$user_count;  
                
        return $result_data;       
    }
    
    /* Users Spent Time at Store End */
    
    /* Redeemed deals of Vendors start by iflair on 07 june 2013  */
    
        public function get_vendor_redeemed_deals($userid)
        {
            $this->db->select("redeem_deals.redeem_time,users.firstname,deals.name");
            $this->db->select('HOUR(redeem_deals.redeem_time) as timehours'); 
            $this->db->select('DAYNAME(redeem_deals.redeem_time) as daysdate'); 
            $this->db->from('redeem_deals');
            
            $month = date("m");
            $this->db->where("date_format(date(redeem_time),'%m') = '".$month."'");
            $this->db->where("user_id",$userid);
            $this->db->join('users','users.id = redeem_deals.user_id','left');
            $this->db->join('deals','deals.id = redeem_deals.deal_id','left');
            $this->db->order_by("redeem_deals.redeem_time","ASC"); 
            
            return $this->db->get()->result();
            
            /*$this->db->get()->result();
            echo $this->db->last_query();
            exit();*/
        }
        
   /* Redeemed deals of Vendors End */
        
        public function get_new_deals_from_week($startDate,$endDate) {
            $this->db->select('deals.*,deals.id as deal_id,deals.name as deal_name,deals.status as deal_status,stores.*,stores.name as store_name,pages.slug as deal_slug,pages.parent_slug as store_slug');
            $this->db->from('deals');
            $this->db->join('stores','stores.id = deals.store_id','left');
            $this->db->join('locations','locations.id = stores.location_id','left');
            $this->db->join('user_meta','user_meta.meta_value = stores.id','left');
            $this->db->join('users','users.id = user_meta.user_id','left');
            $this->db->join('pages','pages.object_id = deals.id','left');
            $this->db->where('users.status = 1');
            $this->db->where('pages.type = "deal"');
            $this->db->where('user_meta.meta_key = "store_id"');
            $this->db->where('deals.status = "live"');
            $this->db->where('deals.start_time >=',$startDate);
            $this->db->where('deals.start_time <=',$endDate);
            /*$this->db->get()->result();
            echo $this->db->last_query();*/
            return $this->db->get()->result();
        }
        
        public function get_new_stores_from_week() {
            $this->db->select('stores.*,pages.slug');
            $this->db->from('stores');                        
            $this->db->join('user_meta','user_meta.meta_value = stores.id','left');
            $this->db->join('users','users.id = user_meta.user_id','left');
            $this->db->join('pages','pages.object_id = stores.id','left');
            $this->db->where('users.status = 1');
            $this->db->where('pages.type = "store"');
            $this->db->where('stores.is_featured = "1"');
            //$this->db->where('user_meta.meta_key = "store_id"');            
            //$this->db->where('users.registered >=',$startDate);
            //$this->db->where('users.registered <=',$endDate);
            $this->db->limit(1);
            return $this->db->get()->result();
        }
        
        public function get_deal_id($voucher_id) {
				$this->db->select('*');
				$this->db->from('order_items');
        		$this->db->where('order_items.voucher_no',$voucher_id);
        		return $this->db->get()->result();
        		//return $query->row('item_id');
        }
		public function get_voucher_id($id) {
				$this->db->select('*');
				$this->db->from('order_items');
        		$this->db->where('order_items.id',$id);
        		return $this->db->get()->result();
        		//return $query->row('item_id');
        }
}

?>
