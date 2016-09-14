<?php
/*Added new file by iflair 11-12-'12*/
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Home extends CI_Controller {
    
    function __construct()
    {
        parent::__construct();
        
        $this->load->library("form_validation");
        $this->load->model('newsletter_m');
        $this->siterestriction->loggedin_user_access_denied();
        $config = $this->newsletter_m->get_mailchimp_config();        
        $this->load->library('MCAPI', $config, 'mail_chimp');
    }

    /**
     */
    
    public function index() {
        $data['success'] = false;
                
        /*Registration from template changes starts */
        $this->template->register_script('custom_input','customInput.jquery.js',array('jquery'));
        $this->template->enable_script('custom_input');
        
        $this->template->register_script('custom_select','jquery.selectbox-0.2.min.js',array('jquery'));
        $this->template->enable_script('custom_select');
        
        $this->template->register_script('jquer_slider_min_js','jquery.slider.min.js',array('jquery'));
        $this->template->enable_script('jquer_slider_min_js');
        
        $this->template->register_style('register_syle','register_style.css',array('style'));
        $this->template->enable_style('register_syle');
        
        $this->template->register_style('jquery_slider_min_css','jquery.slider.min.css',array('style'));
        $this->template->enable_style('jquery_slider_min_css');
        
        /*Registration from template changes ends */
                
        $this->load->model('country_m');        
        
        //provide list of states
        $states = $this->country_m->get_country_regions('US','region_id,code');
        $data['states'] = $states;
        
        $this->form_validation->set_rules(array(
            array(
                'field' => 'firstname',
                'label' => 'first name',
                'rules' => 'trim|required'
            ),
            array(
                'field' => 'lastname',
                'label' => 'last name',
                'rules' => 'trim|required'
            ),
            array(
                'field' => 'email',
                'label' => 'email',
                'rules' => 'trim|required|valid_email|is_unique[users.login]'
            ),
            array(
                'field' => 'emailconf',
                'label' => 'email conformation',
                'rules' => 'trim|required|matches[email]'
            ),
            array(
                'field' => 'pass',
                'label' => 'password',
                'rules' => 'trim|required|min_length[6]|matches[passconf]'
            ),
            array(
                'field' => 'passconf',
                'label' => 'password conformation',
                'rules' => 'trim|required'
            ),
            
            array(
                'field' => 'state',
                'label' => 'state',
                'rules' => 'trim|required'
            ),
            
            array(
                'field' => 'city',
                'label' => 'city',
                'rules' => 'trim'
            ),
            
            array(
                'field' => 'address',
                'label' => 'address',
                'rules' => 'trim'
            ),
            array(
                'field' => 'zip',
                'label' => 'zip',
                'rules' => 'trim|required|integer'
            ),
        	array(
        		'field' => 'gender_option',
        		'label' => 'Gender',
        		'rules' => 'xss_clean|callback_validation_option_limit_check[gender_option.2]'        		
        	),
        	array(
        		'field' => 'age_option',
        		'label' => 'Age',
        		'rules' => 'xss_clean'
        	),
        	array(
        		'field' => 'ethnicity_option',
        		'label' => 'Ethnicity',
        		'rules' => 'xss_clean|callback_validation_option_limit_check[ethnicity_option.2]'
        	),
        	array(
        		'field' => 'income_option',
        		'label' => 'Income',
        		'rules' => 'xss_clean'
        	),
        	array(
        		'field' => 'code',
        		'label' => 'code',
        		'rules' => 'trim|required|callback_validation_code_check'
        	),
        ));
        
        // If the form validation passed
        
        if ($this->form_validation->run()) 
        {   
            $this->load->model('user_m');            
            
            $user_data = array(
				'login' => $this->input->post('email'),
				'pass' => sha1($this->input->post('pass').$this->config->item('encryption_key')),
				'email' => $this->input->post('email'),
				'registered' => date('Y-m-d H:i:s'),
				'status' => 1,
				'firstname' => $this->input->post('firstname'),
				'lastname' => $this->input->post('lastname'),                
                                'role' => 'customer'            	
            );
            $genderOptions = '';
            $genderOptionArray = $this->input->post('gender_option');
            if(isset($genderOptionArray) && !empty($genderOptionArray)) {
                if(is_array($genderOptionArray)) {
                    $genderOptions = implode(",",$genderOptionArray);
                } else {
                    $genderOptions = $genderOptionArray;
                }
            }
            
            $ageOptions = '';
            $ageOptionArray = $this->input->post('age_option');
            if(isset($ageOptionArray) && !empty($ageOptionArray)) {
                if(is_array($ageOptionArray)) {
                    $ageOptions = implode(",",$ageOptionArray);
                } else {
                    $ageOptions = $ageOptionArray;
                }
            }
            
            $ethnicityOptions = '';
            $ethnicityOptionArray = $this->input->post('ethnicity_option');
            if(isset($ethnicityOptionArray) && !empty($ethnicityOptionArray)) {
                if(is_array($ethnicityOptionArray)) {
                    $ethnicityOptions = implode(",",$ethnicityOptionArray);
                } else {
                    $ethnicityOptions = $ethnicityOptionArray;
                }
            }
            
            $incomeOptions = '';
            $incomeOptionArray = $this->input->post('income_option');
            if(isset($incomeOptionArray) && !empty($incomeOptionArray)) {
                if(is_array($incomeOptionArray)) {
                    $incomeOptions = implode(",",$incomeOptionArray);
                } else {
                    $incomeOptions = $incomeOptionArray;
                }
            }
            $params = array(
            		'privledges' => array(
            				'can_access_account' => true
            		),
            		'user_location' => array(
            				'address' => $this->input->post('address'),
            				'state' => $this->input->post('state'),
            				'city' => $this->input->post('city'),
            				'zip' => $this->input->post('zip')
            		),
            		'optional_user_info' => array(
            				'gender_option' => $genderOptions,
            				'age_option' => $ageOptions,
            				'ethnicity_option' => $ethnicityOptions,
            				'income_option' => $incomeOptions
            		)
            );
	
            $send_email = true;
            
            $created_user_id = $this->user_m->create_user($user_data,$send_email,$params,true);
            
            $newsletter_user_data = array(
                'newsletter_email' => $this->input->post('email'),
                'user_id' => $created_user_id,
                'datecreated' => date("Y-m-d h:i:s")
            );
            
            $list_id = $this->newsletter_m->get_default_list_id();
            $useremail = $this->input->post('email');
            $merge_vars = array('FNAME'=>$this->input->post('firstname'), 'LNAME'=>$this->input->post('lastname'));
            $mail_chimp = $this->mail_chimp->listSubscribe($list_id, $useremail);
            
            $is_dup = $this->newsletter_m->get_newsletter_user_email($this->input->post('email'));
            
            if(!$is_dup)
            {
                $newsletter_id = $this->newsletter_m->create_newsletter_user($newsletter_user_data);
            }
            
            $data['success'] = true;
            $data['created_user'] = $created_user_id;
            $data['created_newsletter'] = $newsletter_id;            
        }
        
        $this->load->view('register/home',$data);
        
    }
    
    public function validation_code_check() {
    	$this->load->library('secureimage');
    	if($this->secureimage->check($this->input->post('code')) == false) {
    		$this->form_validation->set_message('validation_code_check','Check security code.');
    		return false;
    	} else {
    		return true;
    	}    	
    }
    
    public function validation_option_limit_check($str,$field) {
        
        list($field_name, $limit)=explode('.', $field);
        if(!empty($field_name)) {
            $field_post_value = $this->input->post($field_name);
            if(isset($field_post_value) && !empty($field_post_value)) {
	    	if(is_array($this->input->post($field_name))) {
	    		if(count($this->input->post($field_name)) > $limit) {
                                $this->form_validation->set_message('validation_option_limit_check','You can\'t set more than '.$limit.' options for %s');
	    			return false;
                        }
	    	}
            }	    	
    	}
    	return true;
    }

}