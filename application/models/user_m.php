<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class User_m extends CI_Model{
        
    function __contruct()
    {    	
        //Call the Model constructor
        parent::__construct();        
    }
    
    /* Changed by iflair 11-12-'12  */
    
    public function create_user($data,$send_vendor_mail = true,$params = array(),$setlogin = false) {
        
        $this->db->insert('users',$data);
        $id = $this->db->insert_id();
        		       
        if(isset($send_vendor_mail) && $send_vendor_mail) 
        {        	
        	$this->send_vendor_registration_approval_link_to_admin($id);        	
        }
        
        if(isset($params['privledges']))
        {
                $this->add_meta($id,'privledges',serialize($params['privledges']));
        }
        
        if(isset($params['user_location']))
        {
        	$this->add_meta($id,'user_location',serialize($params['user_location']));
        }
        
        if(isset($params['optional_user_info']))
        {
        	$this->add_meta($id,'optional_user_info',serialize($params['optional_user_info']));
        }
        
        if(isset($setlogin) && $setlogin) 
        {
        	$this->session->set_userdata('created_user',$id);
        	$res = $this->verify_user($data['login'],$data['pass'],true);        	
        	$this->auth->login($res);
        }
        
        return $id;
    }
    
    
    public function verify_user($login,$password,$skip_encr = false){
        
    	if($skip_encr) {
        	$query = $this->db->where('login',$login)->where('pass',$password)->limit(1)->get('users');
    	} else {
    		$query = $this->db->where('login',$login)->where('pass',sha1($password.$this->config->item('encryption_key')))->limit(1)->get('users');
    	}
        
        if($query->num_rows() > 0)
        {       		
          return $query->row();
            
        }
        else
        {  
            // check to see if an unsalted version is stored in the db, and then salt it
            $query = $this->db->where('login',$login)->where('pass',sha1($password))->limit(1)->get('users');
            if($query->num_rows() > 0)
            {
                $res = $query->row();
                $this->update_user($res->id, array('pass' => sha1($password.$this->config->item('encryption_keyencryption_key'))));
                return $res;
            }            
            return false;            
        }
    } 
    
    public function update_user($id,$data = array(),$params = array()){
        
        $this->db->where('id',$id);
        $this->db->update('users',$data);
        
        if(isset($params['privledges'])){
        	$this->add_meta($id,'privledges',serialize($params['privledges']));
        }
        
        if(isset($params['user_location'])){
        	$this->add_meta($id,'user_location',serialize($params['user_location']));
        }
        
        if(isset($params['optional_user_info'])){
        	$this->add_meta($id,'optional_user_info',serialize($params['optional_user_info']));
        }
        
    }
    
    public function Update_pass($id,$data){
        
        $this->db->where('id',$id);
        $this->db->update('users',$data);
        return true;
    }
    
    public function check_old_pass($id,$old_pass)
    {
        $this->db->select('*');
        $this->db->from('users');
        $this->db->where('id',$id);
        $this->db->where('pass',$old_pass);
        $total_row = $this->db->count_all_results();
        return $total_row;
    }
    
    public function user_data($user_id)
    {
          $this->db->select('*'); 
          $this->db->where('id',$user_id);
          $query = $this->db->get("users"); 
          $row = $query->result_array();
           
          return $row;
    }
    
    public function user_firstname($user_id)
    {
          $this->db->select('firstname'); 
          $this->db->where('id',$user_id);
          
          $res = $this->db->get('users')->row();
          $u_name = $res->firstname;  
          return $u_name;
    }
    
    public function get_user($id,$commaseparatedcolumns = '*') {
    	if(isset($id)) {
	    	if($commaseparatedcolumns != '*') {
	    		$this->db->select($commaseparatedcolumns);
	    	}
	    	$query = $this->db->get_where('users',array('id' => $id));
	    	if($query->num_rows() > 0){
	    		return $query->row();
	    	} else {
	    		return false;
	    	}
    	}
    }
        
    public function add_meta($user_id, $meta_key, $meta_value){
        
        $data = array(
            'user_id' => $user_id,
            'meta_key' => $meta_key,
            'meta_value' => $meta_value
        );
                
        if($this->get_meta($user_id,$meta_key)){
            $this->update_meta($user_id, $meta_key, $meta_value);
        }else{
            $this->db->insert('user_meta',$data);
        }
    }
    
    public function get_meta($user_id,$key)
    {   
        $query = $this->db->get_where('user_meta', array('user_id' => $user_id, 'meta_key' => $key));
        
        if($res = $query->row()) $res = $res->meta_value;
       
        return $res;
    }
        
    public function get_user_meta_key($user_id,$key)
    {
         $query = $this->db->get_where('user_meta', array('user_id' => $user_id, 'meta_key' => $key));
        
         if($res = $query->row()) $res = $res->meta_key;
       
        return $res;
    }
    
    public function get_store_meta($key){
        
        $query = $this->db->get_where('user_meta', array('meta_key' => $key));
        
        if($res = $query->row()) $res = $res->user_id;
       
        return $res;
    }
    
    public function update_meta($user_id, $meta_key, $meta_value)
    {
        $data = array('meta_value' => $meta_value);
        
        $this->db->where(array('user_id' => $user_id, 'meta_key' => $meta_key));
        $this->db->update('user_meta',$data);        
    }
    
    public function remove_meta($user_id, $meta_key){
        
        $this->db->where(array('user_id'=>$user_id,'meta_key' => $meta_key));
        $this->db->delete('user_meta');
    }
    
    public function remove_user_meta($user_id){
        
        $this->db->where('user_id',$user_id);
        $this->db->delete('user_meta');
    }
    
    /* Added by iflair 11-12-'12  */
    
    public function send_vendor_registration_approval_link_to_admin($user_id) 
    {
	if(isset($user_id)) 
        {
            $config = array('mailtype' => 'html');    	                
            $this->load->library('email',$config);
            $admin_users = $this->get_admin_users();
            $status_link = base_url('admin/approve_vendor/'.$user_id);
            $subject = 'New Vendor Signup Approval';
            $message = '<div>Click below link to review / approve new vendor account.</div>';
            $message .= '<a href="'.$status_link.'">'.$status_link.'</a>';
            $from = 'test@test.com'; //TO-DO change email.			
            if(is_array($admin_users)) 
            {                
                    foreach($admin_users as $admin_user) 
                    {
                            $admin_email = $admin_user->email;
                            $this->email->to($admin_email);
                            $this->email->from($from);
                            $this->email->subject($subject);
                            $this->email->message($message);
                            $this->email->send();
                    }
            }		
        } 
        else 
        {
            return false;
        }
   }
	
	public function send_approval_notification_to_vendor($user_id) {
            
		if(isset($user_id)) {
                        $config = array('mailtype' => 'html');    	                
                        $this->load->library('email',$config);
			$subject = 'Your Account Approved as Vendor';
			$message = '<div>Your account has been approved as vendor. You can now access your account.</div>';			
			$from = 'test@test.com'; //TO-DO change email.
			$user = $this->get_user($user_id,'email');
			$this->email->to($user->email);
			$this->email->from($from);
			$this->email->subject($subject);
			$this->email->message($message);
			$this->email->send();
		} else {
			return false;
		}
	}

	/*
	 * Added by iflair 11-12-'12
	 */
	public function get_user_role_by_id($userid) {
            
		$this->db->select('role');
		$query = $this->db->get_where('users', array('id' => $userid));
		if($query->num_rows() > 0){
			$items = $query->result();
                        
                        foreach($items as $val)
                        {
                           return  $val->role;
                        }
                        
		} else {
			return false;
		}       
	}
        
        public function get_admin_users() {
            
		$this->db->select('id,email');
		$query = $this->db->get_where('users', array('role' => 'admin', 'status' => 1));
		if($query->num_rows() > 0){
			return $query->result();
		} else {
			return false;
		}       
	}
        
	public function get_vendors($limit) {
            
		$this->db->select('*');
		$this->db->where('role !=','admin');
		$this->db->from('users');
		$this->db->limit($limit);
		$this->db->join('user_meta','user_meta.user_id = users.id and user_meta.meta_key = "store_id"','left');
		//$users = $this->db->get('users')->result();
		$users = $this->db->get()->result();
		$query = $this->db->query("SELECT FOUND_ROWS() AS 'total'");
                $total = $query->row()->total;
		$results = array(
            	'users' => $users,
            	'total' => $total
		);	  
		return $results;
	}
	
	public function update_vendor_status($user_id,$status) 
        {
	    $data = array( 'status' => $status);
            $this->db->where('id',$user_id);
            $res = $this->db->update('users',$data);
            if($res == '1'){
      		return $res;
            } 
	}
        
	public function get_user_by_username($username)
        {            
            $this->db->select('*');
            $hstr = "firstname LIKE '%".$username."%' ";
            $this->db->where($hstr);
            $this->db->where('role','store_admin');
            $users = $this->db->get('users')->result();
            $query = $this->db->query("SELECT FOUND_ROWS() AS 'total'");
            $total = $query->row()->total;
            $results = array('users' => $users,'total' => $total);	        		
            //  echo $this->db->last_query();
            return $results;
      }
        
      public function get_user_by_useremail($useremail)
      {
        $this->db->select('*');
        $hstr = "email = '".$useremail."'";
        $this->db->where($hstr);
        // $this->db->where('role','store_admin');
        $users = $this->db->get('users')->result();
        $query = $this->db->query("SELECT FOUND_ROWS() AS 'total'");
        		$total = $query->row()->total;
					  
	$results = array(
            	'users' => $users,
            	'total' => $total

        		);	        		
      //  echo $this->db->last_query();
        return $results;
      }
	
      public function get_pagination_vendors($params) {
			
	$default = array(
            'limit_start' => 0,
            'limit_end' => 20
        );
                
      if($params['useremail']!=''){
      		$hstr = "email LIKE '%".$params['useremail']."%' ";
       	   $this->db->where($hstr);
      	}
      if($params['username']!=''){
      		$hstr = "firstname LIKE '%".$params['username']."%' ";
       	   $this->db->where($hstr);
      	}	  
		$this->db->select('*');
		$this->db->where('role','store_admin');
		$this->db->limit($params['limit_end'], $params['limit_start']);
		$users = $this->db->get('users')->result();
                
		//echo $this->db->last_query();
                /*$query = $this->db->query("SELECT FOUND_ROWS() AS 'total'");
        	$total = $query->row()->total;
					  
				 $results = array(
            	'users' => $users,
            	'total' => $total

        		);	    */
                
		return $users;
	}
		
	/*for sales chart */
        
        public function get_total_sales($data,$user_id,$time_val="",$income_sales){

          $this->db->select('deals.name');

          if($income_sales == "sales")
          {
              $this->db->select_sum('order_items.item_quantity');
          }
          else if($income_sales == "income")
          {
              $this->db->select_sum('order_items.item_price');
          }

          $date = date('Y-m-d');

          $w_start_date = date('Y-m-d', strtotime('Monday', time()));
          $w_end_date = date('Y-m-d', strtotime('Sunday', time()));

          $m_start_date = date('Y-m-01'); 
          $m_end_date = date('Y-m-t');			

          $year   = date("Y");
          $y_start_date = date('Y-m-d', strtotime($year."-01-01"));
          $y_end_date  = date('Y-m-d', strtotime($year."-12-31"));


          $this->db->from('order_items');
          $this->db->join('deals','order_items.item_id = deals.id','left');
          $this->db->join('order','order.id = order_items.order_id' ,'left');

         /*if($time_val=='Daily'){
                        $dateval = "order.process_date LIKE '".$date."%' ";
                        $this->db->where($dateval);
            }
            elseif($time_val=='Weekly'){
                        $this->db->where("order.process_date BETWEEN '".$w_start_date."%' and '".$w_end_date."%'", NULL, FALSE);		
            }
            elseif($time_val=='Monthly'){
                     $this->db->where("order.process_date BETWEEN '".$m_start_date."%' and '".$m_end_date."%'", NULL, FALSE);		
            }
            elseif($time_val=='Yearly'){
                     $this->db->where("order.process_date BETWEEN '".$y_start_date."%' and '".$y_end_date."%'", NULL, FALSE);		
            }*/


         // $this->db->where('order_items.user_id != '.$user_id);

                foreach($data as $val)
                {
                    $vari[] =  $val;        		
                }

                $ids = implode(',',$vari);
                        $where = "item_id IN (".$ids.")";
                        $this->db->where($where);

                $this->db->group_by('order_items.item_id');
                $items = $this->db->get()->result();
                 // echo $this->db->last_query();	                        
                return $items;

        }

        /*for Redeemed deal chart */	
        public function get_redeemed_deals($data,$user_id)
        {
                        $vari = array();
                        $this->db->select("count(*) as Deal_Count");
                        $this->db->select('deals.name');

                        $this->db->from('redeem_deals');
                        $this->db->join('deals','redeem_deals.deal_id = deals.id','left');	
                        
                        foreach($data as $val) { $vari[] =  $val; }
                        
                $ids = implode(',',$vari);
                        $where = "deal_id IN (".$ids.")";
                        $this->db->where($where);
                        $this->db->where('redeem_deals.user_id',$user_id);
                        $this->db->group_by('redeem_deals.deal_id');
                        $this->db->having("count(*) >= 1 "); 
                        $items = $this->db->get()->result();

                //echo $this->db->last_query();
                return $items;	
        }

        public function get_voucher_numbers($data,$user_id)
        {				
                $this->db->select('*');

                foreach($data as $val){
                        $vari[] =  $val;        		
                }

                $ids = implode(',',$vari);
                        $where = "deal_id IN (".$ids.")";
                        $this->db->where($where);
                        $this->db->where('user_id',$user_id);
                        $this->db->from('redeem_deals');


                        return $this->db->get()->result();
                        //echo $this->db->last_query();
                        //return $query->result();	
        }

        public function get_unredeemed_deals($data,$voucher_data,$user_id)
        {
                        $this->db->select("count(*) as Deal_Count");
                        $this->db->select('deals.name');

                        $this->db->from('order_items');
                        $this->db->join('deals','order_items.item_id = deals.id','left');	

                        foreach($data as $val){
                                $vari[] =  $val;        		
                }
                $ids = implode(',',$vari);
                        $where = "order_items.item_id IN (".$ids.")";
                        $this->db->where($where);

                        if(count($voucher_data) > 0){
                        foreach($voucher_data as $value){
                                $voucher[] =  $value;        		
                }

                $vids = implode("','",$voucher);

                        $where1 = "order_items.voucher_no  NOT IN ('".$vids."')";
                        $this->db->where($where1);
                        }
                        $this->db->where('order_items.user_id !='.$user_id);
                        $this->db->group_by('order_items.item_id');
                        $this->db->having("count(*) >= 1 "); 
                        $items = $this->db->get()->result();

                        //echo $this->db->last_query();
                return $items;	
        }

        public function get_meta_optional_user_info($userid="")
        {
              //$query = $this->db->get_where('user_meta', array('meta_key' => 'optional_user_info'));
              $this->db->select("meta_value");
              $this->db->from('user_meta');
              if($userid)
              {
                  $this->db->where("user_id",$userid);
              }
              $this->db->where('meta_key = "optional_user_info"');

              $res = $this->db->get()->result();

              return $res;  
        }                

        public function get_meta_optional_customer_info($userid=array())
        {
              //$query = $this->db->get_where('user_meta', array('meta_key' => 'optional_user_info'));
              $this->db->select("meta_value");
              $this->db->from('user_meta');
              
              if(!empty($userid)) 
              {
                $where = "user_id IN (".$userid.")";
                $this->db->where($where);
              }
              $this->db->where('meta_key = "optional_user_info"');

              $res = $this->db->get()->result();

              //echo $this->db->last_query();

              return $res;  
        }  

        public function get_vendor_by_storeid($storeid)
        {
            $res = array();
            if($storeid) {
                $this->db->select('u.firstname');
                $this->db->join('user_meta','u.id = user_meta.user_id','left');
                $this->db->where('user_meta.meta_value = '.$storeid);
                $this->db->from('users as u,stores as s');
                $this->db->group_by('u.id');

                if($res = $this->db->get()->row_array()) $res = $res['firstname'];                
            }
            return $res;
        }
        
        public function get_user_by_storeid($storeid)
        {
                $this->db->select('u.id');
                $this->db->join('user_meta','u.id = user_meta.user_id','left');
                $this->db->where('user_meta.meta_value = '.$storeid);
                $this->db->from('users as u,stores as s');
                $this->db->group_by('u.id');

                if($res = $this->db->get()->row_array()) $res = $res['id'];
                return $res;
        }
        
        public function sort_user_by_role($user_type="")
        {
            $this->db->select('*');
            
            if($user_type)
            { $this->db->where('role',$user_type); }
            
            $this->db->where('role !=',"admin");
            $this->db->from('users');
            $this->db->join('user_meta','user_meta.user_id = users.id and user_meta.meta_key = "store_id"','left');
            $users = $this->db->get()->result();
            $query = $this->db->query("SELECT FOUND_ROWS() AS 'total'");
            $total = $query->row()->total;
            $results = array(
            'users' => $users,
            'total' => $total
            );	  
            return $results;
        }

        public function verify_email($email)
        {
            $query = $this->db->where('login',$email)->limit(1)->get('users');

            if($query->num_rows() > 0)
            {
                return $query->row();
            }
            else
            {
                return false;
            }
        }

        public function send_userpassword($email_id,$password)
        {                    
                if(isset($email_id)) 
                {
                    $config = array('mailtype' => 'html');    	                
                    $this->load->library('email',$config);
                    $subject = 'Password for your login';
                    $message = '<div>Your Login id and password are as follows.</div>';
                    $message .= '<div>Login :'.$email_id.'</div>';
                    $message .= '<div>Password :'.$password.'</div>';
                    $from = 'test@test.com'; //TO-DO change email.			

                    $this->email->to($email_id);
                    $this->email->from($from);
                    $this->email->subject($subject);
                    $this->email->message($message);
                    $this->email->send();
                }
                else 
                {
                   return false;
                }		
        }

        public function update_userpassword($user_id,$pass)
        {
            $data = array( 'pass' => $pass);
            $this->db->where('id',$user_id);
            $res = $this->db->update('users',$data);
        } 

        public function edit_change($user_id,$data) 
        {
            $this->db->where('id',$user_id);
            $res = $this->db->update('users',$data);

            return $res;
        }
}

?>
