<?php defined('BASEPATH') OR exit('No direct script access allowed');
/*
 * Sign_up Controller
 */
class Sign_up extends CI_Controller {

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		// Load the necessary stuff...
		$this->load->config('account/account');
		$this->load->helper(array('language', 'account/ssl', 'url'));
		$this->load->library(array('account/authentication', 'account/authorization', 'account/recaptcha', 'form_validation'));
		$this->load->model(array('account/account_details_model', 'account/account_model'));
		$this->load->language(array('general', 'account/sign_up', 'account/connect_third_party'));
	}

	/**
	 * Account sign up
	 *
	 * @access public
	 * @return void
	 */
	function index()
	{
		// Enable SSL?
		maintain_ssl($this->config->item("ssl_enabled"));

		// Redirect signed in users to homepage
		if ($this->authentication->is_signed_in()) redirect('');

		// Check recaptcha
		$recaptcha_result = $this->recaptcha->check();

		// Store recaptcha pass in session so that users only needs to complete captcha once
		if ($recaptcha_result === TRUE) $this->session->set_userdata('sign_up_recaptcha_pass', TRUE);

		// Setup form validation
		$this->form_validation->set_error_delimiters('<span class="field_error">', '</span>');
		$this->form_validation->set_rules(array(array('field' => 'sign_up_username', 'label' => 'lang:sign_up_username', 'rules' => 'trim|required|alpha_dash|min_length[2]|max_length[24]|callback_username_check'), array('field' => 'sign_up_password', 'label' => 'lang:sign_up_password', 'rules' => 'trim|required|min_length[6]'), array('field' => 'sign_up_email', 'label' => 'lang:sign_up_email', 'rules' => 'trim|required|valid_email|max_length[160]|callback_email_check'), array('field' => 'sign_up_password_confirm', 'label' => 'lang:sign_up_password_confirm', 'rules' => 'trim|required|min_length[6]|matches[sign_up_password]')));

		// Run form validation
		if (($this->form_validation->run() === TRUE) && ($this->config->item("sign_up_enabled")))
		{
			// Either already pass recaptcha or just passed recaptcha
			if ( ! ($this->session->userdata('sign_up_recaptcha_pass') == TRUE || $recaptcha_result === TRUE) && $this->config->item("sign_up_recaptcha_enabled") === TRUE)
			{
				$data['sign_up_recaptcha_error'] = $this->input->post('recaptcha_response_field') ? lang('sign_up_recaptcha_incorrect') : lang('sign_up_recaptcha_required');
			}
			else
			{
				// Remove recaptcha pass
				$this->session->unset_userdata('sign_up_recaptcha_pass');

				// Create user
				$user_id = $this->account_model->create($this->input->post('sign_up_username', TRUE), $this->input->post('sign_up_email', TRUE), $this->input->post('sign_up_password', TRUE));

				// Add user details (auto detected country, language, timezone)
				$this->account_details_model->update($user_id);

				// Auto sign in?
				if ($this->config->item("sign_up_auto_sign_in"))
				{
					// Run sign in routine
					$this->authentication->sign_in($this->input->post('sign_in_username_email', TRUE), $this->input->post('sign_in_password', TRUE), $this->input->post('sign_in_remember', TRUE));
				}
				redirect('account/sign_in');
			}
		}

		// Load recaptcha code
		if ($this->config->item("sign_up_recaptcha_enabled") === TRUE) if ($this->session->userdata('sign_up_recaptcha_pass') != TRUE) $data['recaptcha'] = $this->recaptcha->load($recaptcha_result, $this->config->item("ssl_enabled"));

		// Load sign up view
		$this->load->view('sign_up', isset($data) ? $data : NULL);
	}

	/**
	 * Check if a username exist
	 *
	 * @access public
	 * @param string
	 * @return bool
	 */
	function username_check($username)
	{
		if($this->account_model->get_by_username($username))
		{
			return TRUE;
		}
		else
		{
			$this->form_validation->set_message('sign_up_username', 'lang:sign_up_username_taken');
			return FALSE;
		}
	}

	/**
	 * Check if an email exist
	 *
	 * @access public
	 * @param string
	 * @return bool
	 */
	function email_check($email)
	{
		if($this->account_model->get_by_email($email))
		{
			return TRUE;
		}
		else
		{
			$this->form_validation->set_message('sign_up_email', 'lang:');
			return FALSE;
		}
	}

}


/* End of file Sign_up.php */
/* Location: ./application/controllers/account/Sign_up.php */