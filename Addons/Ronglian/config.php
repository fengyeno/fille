<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: yangweijie <yangweijiester@gmail.com> <code-tech.diandian.com>
// +----------------------------------------------------------------------

	return array(
		'accountSid'=>array(
			'title'=>'主帐号(accountSid):',
            'type'=>'text',
            'value'=>'',
		),
		'accountToken'=>array(
			'title'=>'主帐号Token(accountToken):',
            'type'=>'text',
            'value'=>'',
		),
		'appId'=>array(
			'title'=>'应用Id(appId):',
			'type'=>'text',
			'value'=>'',
		),
        'Rest_URL1'=>array(
            'title'=>'Rest URL(开发地址,请不要随意改动):',
            'type'=>'text',
            'value'=>'sandboxapp.cloopen.com',
        ),
        'Rest_URL2'=>array(
            'title'=>'Rest URL(生产地址，请不要随意改动):',
            'type'=>'text',
            'value'=>'app.cloopen.com',
        ),
        'type'=>array(
            'title'=>'状态:',
            'type'=>'select',
            'options'=>array(
                '1'=>'开发',
                '2'=>'生产',
            ),
            'value'=>'1'
        ),
	);