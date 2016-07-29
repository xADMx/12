<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api_model extends CI_Model {

	public function __construct()
	{
		// Call the CI_Model constructor
		parent::__construct();
	}

	public function get_rules_id_public_str()
	{
		$query = $this->db->select('ID_Rules, Name')->where('Public_Reg', 1)->get('rules');
		$rules = '';
		foreach ($query->result() as $row) {
			$rules .= $row->ID_Rules . ',';
		}

		return rtrim($rules, ',');
	}

	public function get_rules_id_select()
	{
		$query = $this->db->select('ID_Rules, Name')->where('Public_Reg', 1)->get('rules');
		foreach ($query->result() as $row) {
			$data[$row->ID_Rules] = $row->Name;
		}

		return $data;
	}

	public function reg()
	{
		$this->load->helper('string');
		$pass = random_string('alnum', 8);
		$data = [
				'ID_Rules'  => $this->input->post('rules'),
				'Login'     => $this->input->post('email'),
				'Phone'     => $this->input->post('phone'),
				'Pass'  	=> password_hash($pass, PASSWORD_DEFAULT)
		];

		if ($this->db->insert('users', $data)) {
			echo $pass;
/* Расскоментировать перед релизом
			$this->load->library('email');

			$this->email->from('your@example.com', 'Your Name');
			$this->email->to($this->input->post('email'));

			$this->email->subject('Добро пожаловать к нам.');
			$this->email->message('Ваш пароль для входа: ' . $pass . ', логин ваш E-Mail.');

			$this->email->send();
*/
			return true;

		}
		return false;

	}

	public function update_pass()
	{
		$this->load->helper('string');
		return $this->db->update('users', ['Pass' => password_hash(random_string('alnum', 8), PASSWORD_DEFAULT)], ['ID_Users' => $this->session->userdata('ID')]);
	}

	public function delete($id)
	{
		$this->db->delete('users', ['ID_Users' => $id]);
	}

	public function auth()
	{
		$query = $this->db->select('users.id_users, users.id_group_users, users_auth.passwords')
						->join('users_auth', 'users_auth.ID_Users = users.ID_Users', 'left')
						->get_where('users', ['users_auth.login' => $this->input->post('login')], 1);
		$row = $query->row();

		if (isset($row) && password_verify($this->input->post('password'), $row->passwords))
		{
			$newdata = [
					'id'  		=> $row->id_users,
					'group' 	=> $row->id_group_users,
					'valid' 	=> true
			];

			$this->session->set_userdata($newdata);
			return json_encode(['id' => $row->id_users, 'valid' => 1, 'group' => $row->id_group_users]);
		}
			return false;
	}

	public function update()
	{
		$arr = array();
		$objDateTime = new DateTime('NOW');
		$query = $this->db->get_where('users', ['users.last_update' 	=> $objDateTime,
												'users.id_users' 		=> $this->input->post('users')]);
		$row = $query->row();
		$TempArr = $query->result_array();

		if (!empty($TempArr))
			$arr['users'] = $TempArr;

		$query = $this->db->select('*')
						  ->join('users_auth', 'users_auth.ID_Users = users.ID_Users', 'left')
						  ->get_where('users', ['last_update' 	=> $objDateTime,
						   					    'id_users'		=> $this->input->post('users')]);
		$row = $query->row();
		$TempArr = $query->result_array();

		if (!empty($TempArr))
			$arr['users'] = $TempArr;


		if (isset($row) && password_verify($this->input->post('password'), $row->Pass))
		{
			return json_encode(['id' => $row->id_users, 'valid' => 1]);
		}
			return false;
	}

	public function block()
	{
		$expiration = time() - 300;
		$this->db->where('Datetime < ', $expiration)->delete('access_bad');

		if ($this->db->where('ip_address', $this->input->ip_address())->count_all_results('access_bad') < 5) {
			$data = [
					'Datetime' => time(),
					'Login' => $this->input->post('email'),
					'ip_address' => $this->input->ip_address()
			];

			$this->db->insert('access_bad', $data);
			return true;
		} else {
			return false;
		}
	}

	public function logouth()
	{
		$this->session->sess_destroy();
	}

}