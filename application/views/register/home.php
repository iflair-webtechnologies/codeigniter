<?php
/*Added new file by iflair 11-12-'12*/
    $this->load->view('header');
?>
<div id="settings-choose" class="grey-container bb">
    <h1>Register your account</h1>    
    <div class="settings-choices">    	
        <button class="active">Signup as Customer</button>
        <a href="<?php echo base_url('register/vendor');?>"><button class="last">Signup as Vendor</button></a>        
    </div>
</div>

<div class="grey-container bb lead register-frm">
    <h1>Register for your free account</h1>
    <h2>Please enter your information - <span>your privacy is important to us (we promise to keep your information safe from your ex)</span></h2>
</div>

<div id="personal-info" class="light-container bottom">

    <div class="bb form_part">
        
        <?php if(!$success){ ?>
            <?php echo form_open("",array('id' => "registerForm")); ?>
            <div class="validation-errors">
            <?php echo validation_errors(); ?>
            </div>
            
    		<div class="form_field">
      			<div class="form_left_part">
        			<h1>Required information</h1>
                                <h2>You must fill out the following fields to create your account</h2>
                                <h2 id="Required"></h2>
        			<div class="form_input_main">
                                    	
          			<div class="form_input">
            				<label>Enter your first name</label>
            				<span>
                                            <input type="text" class="bb" id="firstname" name="firstname" value="<?php echo set_value('firstname'); ?>" placeholder="Enter first name" />
            				</span>
            			</div>
                                <div id="fnameInfo" class="error_info"></div>
            				
                                <div class="form_input">
            				<label>Enter your last name</label>
            				<span>
            					<input type="text" class="bb" id="lastname" name="lastname" value="<?php echo set_value('lastname'); ?>" placeholder="Enter last name" />
            				</span>
            			</div>
                                <div id="lnameInfo" class="error_info"></div>
            				
          			<div class="form_input">
            				<label>Enter a valid e-mail</label>
            				<span>
                                            <input type="text" class="bb" id="email_address" name="email" value="<?php echo set_value('email'); ?>" placeholder="Enter email address" />
            				</span>
            			</div>
            			<div id="emailinfo" class="error_info"></div>
                                
          			<div class="form_input">
            				<label>Confirm e-mail</label>
            				<span>
                                            <input type="text" class="bb" id="emailconf" name="emailconf" value="<?php echo set_value('emailconf'); ?>" placeholder="Confirm email address" />
            				</span>
            			</div>
            			<div id="emailconfinfo" class="error_info"></div>	
                                <div class="form_input">
            				<label>Choose a password</label>
            				<span>
                                            <input type="password" class="bb" id="pass" name="pass" value="<?php //echo set_value('pass'); ?>" placeholder="Enter password" />
            				</span>
            			</div>
            			<div id="passinfo" class="error_info"></div>	
          			<div class="form_input">
            				<label>Confirm password</label>
            				<span>
                                            <input type="password" class="bb" id="passconf" name="passconf" value="<?php //echo set_value('passconf'); ?>" placeholder="Confirm password" />
            				</span>
            			</div>
            			<div id="passconfinfo" class="error_info"></div>	
          			<div class="form_input">
            				<label>Enter your address</label>
            				<span>
                                            <input type="text" class="bb" id="address" name="address" value="<?php echo set_value('address'); ?>" placeholder="Optional" />
            				</span>
            			</div>
            			<div id="addressinfo" class="error_info"></div>
          			<div class="form_input">
            				<label>Enter state</label>
            				<span>            					
            					<span class="ca float-left">
            						<select name="state" id="country_id" tabindex="1">            							
            							<?php            					
		            						foreach($states as $state) {
		            					?>            									            							
		            							<option value="<?php echo $state->region_id;?>" <?php echo set_select('state', $state->region_id); ?>>
		            								<?php echo $state->code;?>
		            							</option>
		            					<?php
		            						}
            							?>              							
            						</select>
            					</span>
            					<span class="enter float-left">Enter Zip Code</span>
            					<span class="enter_in float-right">
                                                    <input type="text" class="bb" id="zip" name="zip" value="<?php echo set_value('zip'); ?>" placeholder="Zip Code" />
            					</span>
            				</span>
            			</div>
                                <div id="zipinfo" class="error_info"></div>
            			<div class="form_input">
            				<label>Select City</label>
                                        <span>
            				<span class="ca float-left">
                                        <div class="select_new">
                                            <select id="city_list" name="city">
                                                <?php
                                                    $postedState = $this->input->post('state');                                                    
                                                    if(isset($postedState) && !empty($postedState)) {
                                                    $cities = $this->country_m->get_city($postedState);
                                                    foreach($cities as $city) { ?>
                                                      <option value="<?php echo $city['city_id'];?>" <?php echo set_select('city', $city['city_id']); ?>>
		            								<?php echo $city['city_name'];?>
		            							</option>  
                                                   <?php }
                                                } else {?>
                                              <option value="2220">San Diego</option>
                                              <option value="2223">San Francisco</option>
                                              <?php }?>
                                            </select>
					</div>
                                        </span>
					</span>
            			</div>            			
          				<div class="form_input enter_test">
          					Are you human? Please enter the letters below
          				</div>
          				
          				<div class="form_input last">
            				<div class="left">
            					<!--<img title="Click" alt="Click" src="<?php echo base_url('assets/images/click.png');?>">-->
            					<?php $captcha_data['imgid'] = 'siimage';?>            					
            					<?php $this->load->view('captcha',$captcha_data);?>
            				</div>
            				<span class="tzq">            					
                                            <input type="text" class="bb" id="code" name="code" placeholder="Enter code" />
            				</span>
            			</div>
                                <div id="codeinfo" class="error_info"></div>
        			</div>
      			</div>
      			
      			<div class="form_left_part float-right">
        			<h1>Optional information</h1>
        			<h2>While not required, your answers help us get to know you</h2>
        			<div class="form_input_main right">        				
          				<div class="radio_blog">
            				<h1>What is your gender? (Select all that apply)</h1>
            				<div class="radio_blog_main">
              					<div class="radio_blog_left">
                					<div class="radio_main">
                						<div class="custom-radio">                						
               								<input type="checkbox" value="Male (Straight)" id="mail_straight_gen" name="gender_option[]" class="gender_option" <?php echo set_checkbox('gender_option', 'Male (Straight)'); ?>>
               								<label for="mail_straight_gen">Male (Straight)</label>
               							</div>                						
                  						<div class="custom-radio">
                							<input type="checkbox" value="Male (Gay)" id="male_gay_gen" name="gender_option[]" class="gender_option" <?php echo set_checkbox('gender_option', 'Male (Gay)'); ?>>
                							<label for="male_gay_gen">Male (Gay)</label>
                						</div>                	
                 						<div class="custom-radio">
               								<input type="checkbox" value="Male (Other)" id="male_other_gen" name="gender_option[]" class="gender_option" <?php echo set_checkbox('gender_option', 'Male (Other)'); ?>>
               								<label for="male_other_gen">Male (Other)</label>
               							</div>                
                					</div>
              					</div>              						
              					<div class="radio_blog_left">
                					<div class="radio_main">
                						<div class="custom-radio">                  						
											<input type="checkbox" value="Female (Straight)" id="female_straight_gen" name="gender_option[]" class="gender_option" <?php echo set_checkbox('gender_option', 'Female (Straight)'); ?>>
											<label for="female_straight_gen">Female (Straight)</label>
										</div>
                                 		<div class="custom-radio">					
											<input type="checkbox" value="Female (Gay)" id="female_gay_gen" name="gender_option[]" class="gender_option" <?php echo set_checkbox('gender_option', 'Female (Gay)'); ?>>
											<label for="female_gay_gen">Female (Gay)</label>
										</div>               		
                  						<div class="custom-radio">
               								<input type="checkbox" value="Female (Other)" id="female_other_gen" name="gender_option[]" class="gender_option" <?php echo set_checkbox('gender_option', 'Female (Other)'); ?>>
               								<label for="female_other_gen">Female (Other)</label>
               							</div>
               						</div>
           						</div>
          					</div>
       					</div>
       					<div class="radio_blog">
          					<h1>What is your age?</h1>
           					<div class="radio_blog_main">
           						<div class="radio_blog_left">
           							<div class="radio_main">
           								<div class="custom-radio">                   						
                   							<input type="checkbox" value="18 - 21 years old" id="18_21_age" name="age_option[]" class="age_option" <?php echo set_checkbox('age_option', '18 - 21 years old'); ?>>
                   							<label for="18_21_age">18 - 21 years old</label>
                   						</div>
										<div class="custom-radio">
                   							<input type="checkbox" value="22 - 30 years old" id="22_30_age" name="age_option[]" class="age_option" <?php echo set_checkbox('age_option', '22 - 30 years old'); ?>>
                   							<label for="22_30_age">22 - 30 years old</label>
                   						</div>
                   						<div class="custom-radio">
                   							<input type="checkbox" value="31 - 40 years old" id="31_40_age" name="age_option[]" class="age_option" <?php echo set_checkbox('age_option', '31 - 40 years old'); ?>>
											<label for="31_40_age">31 - 40 years old</label>
										</div>                   						
               						</div>
           						</div>
           						<div class="radio_blog_left">
              						<div class="radio_main">
              							<div class="custom-radio">                  							                   						
											<input type="checkbox" value="41 - 50 years old" id="41_50_age" name="age_option[]" class="age_option" <?php echo set_checkbox('age_option', '41 - 50 years old'); ?>>
                   							<label for="41_50_age">41 - 50 years old</label>
                   						</div>
                   						<div class="custom-radio">
                   							<input type="checkbox" value="51 - 60 years old" id="51_60_age" name="age_option[]" class="age_option" <?php echo set_checkbox('age_option', '51 - 60 years old'); ?>>
                   							<label for="51_60_age">51 - 60 years old</label>
                   						</div>
                   						<div class="custom-radio">
                   							<input type="checkbox" value="Over 60 years old" id="over_60_age" name="age_option[]" class="age_option" <?php echo set_checkbox('age_option', 'Over 60 years old'); ?>>
                   							<label for="over_60_age">Over 60 years old</label>
                   						</div>
              						</div>
           						</div>
           					</div>
       					</div>
       					<div class="radio_blog">
          					<h1>What is your ethnicity? (Select all that apply)</h1>
           					<div class="radio_blog_main">
           						<div class="radio_blog_left">
              						<div class="radio_main">
              							<div class="custom-radio">                  							                   						
                   							<input type="checkbox" value="White/Caucasian" id="wc_eth" name="ethnicity_option[]" class="ethnicity_option" <?php echo set_checkbox('ethnicity_option', 'White/Caucasian'); ?>>
                   							<label for="wc_eth">White/Caucasian</label>
										</div>
										<div class="custom-radio">
                   							<input type="checkbox" value="Black/African American" id="ba_eth" name="ethnicity_option[]" class="ethnicity_option" <?php echo set_checkbox('ethnicity_option', 'Black/African American'); ?>>
                   							<label for="ba_eth">Black/African American</label>
                   						</div>
                   						<div class="custom-radio">
                   							<input type="checkbox" value="Hispanic/Latino" id="hl_eth" name="ethnicity_option[]" class="ethnicity_option" <?php echo set_checkbox('ethnicity_option', 'Hispanic/Latino'); ?>>
                   							<label for="hl_eth">Hispanic/Latino</label>
                   						</div>                   						
              						</div>
           						</div>
           						<div class="radio_blog_left">
               						<div class="radio_main">
               							<div class="custom-radio">                                     						
                   							<input type="checkbox" value="Native American" id="na_eth" name="ethnicity_option[]" class="ethnicity_option" <?php echo set_checkbox('ethnicity_option', 'Native American'); ?>>
                   							<label for="na_eth">Native American</label>
                   						</div>
                   						<div class="custom-radio">
                   							<input type="checkbox" value="Pacific Islander" id="pi_eth" name="ethnicity_option[]" class="ethnicity_option" <?php echo set_checkbox('ethnicity_option', 'Pacific Islander'); ?>>
                   							<label for="pi_eth">Pacific Islander</label>
                   						</div>
                   						<div class="custom-radio">
                   							<input type="checkbox" value="Other" id="other_eth" name="ethnicity_option[]" class="ethnicity_option" <?php echo set_checkbox('ethnicity_option', 'Other'); ?>>
                   							<label for="other_eth">Other</label>
                   						</div>
               						</div>
           						</div>
          					</div>
       					</div>
       					<div class="radio_blog">
          					<h1>What is your yearly household income?</h1>
           					<div class="radio_blog_main">
           						<div class="radio_blog_left">
              						<div class="radio_main">
              							<div class="custom-radio">                  			                   						
                   							<input type="checkbox" value="Under 20k" id="under_20_in" name="income_option[]" class="income_option" <?php echo set_checkbox('income_option', 'Under 20k'); ?>>
                   							<label for="under_20_in">Under 20k</label>
                   						</div>
                   						<div class="custom-radio">
                   							<input type="checkbox" value="20k - 40k" id="20_40_in" name="income_option[]" class="income_option" <?php echo set_checkbox('income_option', '20k - 40k'); ?>>
                   							<label for="20_40_in">20k - 40k</label>
                   						</div>
                   						<div class="custom-radio">
                   							<input type="checkbox" value="40k - 60k" id="40_60_in" name="income_option[]" class="income_option" <?php echo set_checkbox('income_option', '40k - 60k'); ?>>
                   							<label for="40_60_in">40k - 60k</label>
                   						</div>                   						
              						</div>
           						</div>
           						<div class="radio_blog_left">
              						<div class="radio_main">
              							<div class="custom-radio">                  					                   						
                   							<input type="checkbox" value="60k - 80k" id="60_80_in" name="income_option[]" class="income_option" <?php echo set_checkbox('income_option', '60k - 80k'); ?>>
                   							<label for="60_80_in">60k - 80k</label>
										</div>
										<div class="custom-radio">
                   							<input type="checkbox" value="80k - 100k" id="80_100_in" name="income_option[]" class="income_option" <?php echo set_checkbox('income_option', '80k - 100k'); ?>>
                   							<label for="80_100_in">80k - 100k</label>
										</div>
										<div class="custom-radio">
                   							<input type="checkbox" value="Over 100k" id="over_100_in" name="income_option[]" class="income_option" <?php echo set_checkbox('income_option', 'Over 100k'); ?>>
                   							<label for="over_100_in">Over 100k</label>
                   						</div>
               						</div>
           						</div>
          					</div>
       					</div>
      				</div>
   				</div>
  			</div>
    			
   			<div class="bottom_part">
   				<div class="button">
   					<button type="submit" class="rounded standard green save_info"><span>Save My Information</span></button>   					
   				</div>
   				<div class="bottom_text">
   					You can change this information anytime by returning through the My Account area
   				</div>
  			</div>
            <?php echo form_close(); ?>
        <?php }else{ ?>
            <h4><?php echo ucwords(set_value('firstname')); ?>, your registration was successful!</h4>
            <p>Use your e-mail <?php echo set_value('email'); ?> and password to sign in.</p>
        <?php } ?>
    </div>

</div>

<div class="clr"></div>

<?php
	$preferences = array();
	if($success) {
		if(isset($created_user)) {			
			$preferences['created_user'] = $created_user;
			$this->session->set_userdata('created_user',$created_user);
		}
	}
	$this->load->view('register/preferences',$preferences);
?>
	<!--
    <h5>Address</h5>
    <input type="text" name="address" value="<?php echo set_value('address'); ?>" />
    
    <h5>State</h5>
    <input type="text" name="state" value="<?php echo set_value('state'); ?>" />
    
    <h5>Zip</h5>
    <input type="text" name="zip" value="<?php echo set_value('zip'); ?>" />

   
    
    <h4>Optional</h4>
    
    <h5>Gender</h5>
    
    <h5>Age</h5>
    
    <h5>Ethnicity</h5>
    
    <h5>Income</h5>
    
    
    <h3>Interests</h3>
    
    
    <h5>1</h5>
    <input type="text" name="first" value="" />
    <h5>2</h5>
    <input type="text" name="second" value="" />
    <h5>3</h5>
    <input type="text" name="third" value="" />
    <h5>4</h5>
    <input type="text" name="fourth" value="" />
    <h5>5</h5>
    <input type="text" name="fifth" value="" />
    
    
    <h3>Industry</h3>
    <h5>Accomodation Services</h5>
    <input type="text" name="accomodation" value="" />
    <h5>Beauty Services</h5>
    <input type="text" name="beauty" value="" />
    <h5>Event Services</h5>
    <input type="text" name="event" value="" />
    <h5>Fitness Services</h5>
    <input type="text" name="fitness" value="" />
    <h5>Health Services</h5>
    <input type="text" name="health" value="" />
    <h5>Medical Services</h5>
    <input type="text" name="medical" value="" />
    <h5>Relaxation Services</h5>
    <input type="text" name="relaxation" value="" />
    
    -->
<?php
    $this->load->view('footer');
?>