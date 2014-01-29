<?php
App::uses('AppController', 'Controller');

class IssuesController extends AppController {
	public $uses = ['Project', 'Member', 'User', 'Issue', 'Comment'];
	public function beforeFilter()
	{
		parent::beforeFilter();
	}

	public function index()
	{
		$status = (isset($this->request->query['status']))? $this->request->query['status']:'open';
		$projectId = (isset($this->request->query['project_id']))? $this->request->query['project_id']:null;
		$current_user_id = $this->Session->read('current_user')['User']['id']; 

		//$issue = $this->Issue->find('all', ['conditions'=>['Issue.assignee_id' => $current_user_id]]);
		$issues = $this->Issue->withStatus($status, $projectId);
		$this->set('issues', $issues);
		$this->layout = null;
		$response = $this->render('/Elements/Issues/list');
		$html = $response->__toString();
		header('Content-type: application/json');
		print json_encode(['html' => $html, 'error'=>'']);
		exit;
	}

	public function show()
	{
		$issue = $this->Issue->find('first', ['conditions'=>['Issue.id'=>$this->request->params['id']]]);
		$project_member = $this->Project->find('first', ['conditions'=>['Project.id'=>$issue['Project']['id']]]);
		$comment_user = $this->Comment->find('all', ['conditions'=>['Issue.id'=>$issue['Issue']['id']]]);
		$this->set('issue', $issue);
		$this->set('project_member', $project_member);
		$this->set('comment_user', $comment_user);
	}

	public function registration()
	{
		$project = $this->Project->find('first', ['conditions'=>['id'=>$this->request->params['id']]]);
		if(count($project) === 0) throw new NotFoundException('page not found',404);
		$users = $this->User->fundProjectUserName([$project]);

		// todo class method
		$assign_select = function($project_member) use($users)
		{
			$members[] = "";
			foreach($project_member as $key=>$member){
				$members[$member['user_id']] = $users[$member['user_id']];
			}
			return $members;
		};

		$this->set('project', $project['Project']);
		$this->set('project_member', $assign_select($project['Member']));
		$this->render('new');
	}

	public function create()
	{
		if ($this->request->is('post'))
		{
			$this->Issue->create();

			if ($this->Issue->save($this->request->data['Issue']))
			{
			    $this->Session->setFlash(__('The Issue has been saved'));
			    $this->redirect('/projects/'. $this->request->data['Issue']['project_id']);
			} else {
			    $this->Session->setFlash(__('The Issue could not be saved. Please, try again.'));
			}
		}

		$this->redirect('/projects/');
	}

	public function close()
	{
		/* todo  work_in_progress check
		if current_user.work_in_progress?(@issue)
			current_user.running_workload.update(end_at: Time.now.utc)
		end
		*/

		$error = '';
		if(!$this->Issue->close($this->request->params['id']))
		{
			$error = 'can not Issue status.';
		}else{
			/* todo github
			if @issue.github
        			@issue.github.close(current_user.github.oauth_token)
      			end
      			*/
		}

		$issues = $this->Issue->withStatus('open');
		$this->set('issues', $issues);
		$this->layout = null;
		$response = $this->render('/Elements/Issues/list');
		$html = $response->__toString();
		header('Content-type: application/json');
		print json_encode(['html' => $html, 'error'=>$error]);
		exit;
	}

	public function reopen()
	{
		$error = '';
		if(!$this->Issue->reopen($this->request->params['id']))
		{
			$error = 'can not Issue status.';
		}else{
			/* todo github
			if @issue.github
        			@issue.github.reopen(current_user.github.oauth_token)
      			end
      			*/
		}

		$issues = $this->Issue->withStatus('closed');
		$this->set('issues', $issues);
		$this->layout = null;
		$response = $this->render('/Elements/Issues/list');
		$html = $response->__toString();
		header('Content-type: application/json');
		print json_encode(['html' => $html, 'error'=>$error]);
		exit;

	}

	public function postpone()
	{

	}

	public function doToday()
	{
		
	}
}