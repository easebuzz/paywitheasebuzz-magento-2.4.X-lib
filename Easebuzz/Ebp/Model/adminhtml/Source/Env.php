<?php

namespace Easebuzz\Ebp\Model\Adminhtml\Source;

use Magento\Payment\Model\Method\AbstractMethod;

class Env implements \Magento\Framework\Option\ArrayInterface
{
	public function toOptionArray()
	{
		return array(
				array('value' => 'sandbox','label' => 'Sandbox'),
				array('value' => 'production','label' => 'Production')
				);
	}
}