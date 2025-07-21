<?php
namespace Rissc\Printformer\Plugin\CustomerGroup;

use Rissc\Printformer\Helper\Api;
use Magento\Customer\Model\GroupFactory;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Model\ResourceModel\GroupRepository;
use GuzzleHttp\Exception\GuzzleException;

class AddIdentifierPlugin
{
    /**
     * @var Api
     */
    private $api;

    /**
     * @var GroupFactory
     */
    private $groupFactory;

    /**
     * @param Api          $api
     * @param GroupFactory $groupFactory
     */
    public function __construct(
        Api $api,
        GroupFactory $groupFactory
    ) {
        $this->api = $api;
        $this->groupFactory = $groupFactory;
    }

    /**
     * @param GroupRepository $subject
     * @param GroupInterface  $result
     *
     * @return GroupInterface
     */
    public function afterGetById(GroupRepository $subject, GroupInterface $result): GroupInterface
    {
        $groupId = $result->getId();
        $groupModel = $this->groupFactory->create()->load($groupId);
        $identifier = $groupModel->getData('identifier');

        if ($result->getExtensionAttributes() === null) {
            $extensionAttributes = $result->getExtensionAttributesFactory()->create();
        } else {
            $extensionAttributes = $result->getExtensionAttributes();
        }

        $extensionAttributes->setIdentifier($identifier);
        $result->setExtensionAttributes($extensionAttributes);

        return $result;
    }

    /**
     * @param GroupRepository $subject
     * @param GroupInterface  $result
     *
     * @return GroupInterface
     * @throws GuzzleException
     */
    public function afterSave(GroupRepository $subject, GroupInterface $result): GroupInterface
    {
        $groupId = $result->getId();
        $groupModel = $this->groupFactory->create()->load($groupId);
        $identifier = $groupModel->getData('identifier');

        if (!$identifier) {
            $identifier = $this->api->createUserGroup();
            $groupModel->setData('identifier', $identifier);
            $groupModel->save();
        }

        return $result;
    }
}
