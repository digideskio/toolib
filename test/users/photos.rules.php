<?php

use \toolib\Stupid;

class UserPhotosRest extends Stupid\Template\Rest
{
	public function configure()
	{
		$this->setKeyName('tag');				// Template -> setFact('keyname')
		$this->setKeyRequirements('[[:word:\-]]+'); // Template
		/* $this->setPathPrefix('photos/15/tags');*/
	}
	
	public function appendRules()
	{
		$this->createRule('tag.stats',
			Cond\Request::create()->pathPatternIs('tags/{tag}/stats', array('tag' => '[:word:]+'))
		)->addAction(function(Request $request, $tag){
			echo "Stats for $tag";
		});
	}
	
	public function actionIndex()
	{
		echo 'tag1, tag2, tag3';
	}
	
	public function actionGet($tag)
	{
		echo $tag;
	}
	
	public function actionPost()
	{
		$photo->addTag($tag);
		echo 'Added tag ' .$tag;
	}
	
	public function actionPut($tag)
	{
		$photo->delTag($tag);
		$photo->addTag($this->getRequest()->getContent()->get('tag'));
		
		echo "Updated $tag to " . $this->getRequest()->getContent()->get('tag');
	}
	
	public function actionDelete($tag)
	{
		$photo->delTag($tag);
	}
}




