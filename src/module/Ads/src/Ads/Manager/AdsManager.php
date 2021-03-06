<?php

/**
 * Athene2 - Advanced Learning Resources Manager
 *
 * @author      Jakob Pfab (jakob.pfab@serlo.org)
 * @license     MIT License
 * @license     http://opensource.org/licenses/MIT The MIT License (MIT)
 * @link        https://github.com/serlo-org/athene2 for the canonical source repository
 */
namespace Ads\Manager;

use Ads\Entity\AdInterface;
use Ads\Exception\AdNotFoundException;
use Ads\Hydrator\AdHydrator;
use Attachment\Manager\AttachmentManagerAwareTrait;
use Attachment\Manager\AttachmentManagerInterface;
use Authorization\Service\AuthorizationAssertionTrait;
use ClassResolver\ClassResolverAwareTrait;
use ClassResolver\ClassResolverInterface;
use Common\Traits\ObjectManagerAwareTrait;
use Doctrine\Common\Persistence\ObjectManager;
use Instance\Entity\InstanceInterface;
use Page\Exception\InvalidArgumentException;
use ZfcRbac\Service\AuthorizationService;
use Page\Manager\PageManagerInterface;
use Page\Manager\PageManagerAwareTrait;

class AdsManager implements AdsManagerInterface
{
    
    use ObjectManagerAwareTrait, AuthorizationAssertionTrait;
    use ClassResolverAwareTrait, AttachmentManagerAwareTrait,PageManagerAwareTrait;

    public function __construct(AuthorizationService $authorizationService, AttachmentManagerInterface $attachmentManager, ClassResolverInterface $classResolver, ObjectManager $objectManager, PageManagerInterface $pageManager)
    {
        $this->objectManager = $objectManager;
        $this->classResolver = $classResolver;
        $this->uploadManager = $attachmentManager;
        $this->setAuthorizationService($authorizationService);
        $this->setPageManager($pageManager);
    }

    public function clickAd($ad)
    {
        if (! $ad instanceof AdInterface) {
            $ad = $this->getAd($ad);
        }
        $ad->click();
        $this->objectManager->persist($ad);
    }

    public function createAd(array $data)
    {
        $this->assertGranted('ad.create');
        $data['clicks'] = 0;
        $ad = $this->createAdEntity();
        $hydrator = new AdHydrator();
        $hydrator->hydrate($data, $ad);
        $this->getObjectManager()->persist($ad);
        return $ad;
    }

    public function findAllAds(InstanceInterface $instance)
    {
        $this->assertGranted('ad.get', $instance);
        $criteria = [
            'instance' => $instance->getId()
        ];
        $className = $this->getClassResolver()->resolveClassName('Ads\Entity\AdInterface');
        $ads = $this->getObjectManager()
            ->getRepository($className)
            ->findBy($criteria);
        return $ads;
    }

    public function setAdPage(InstanceInterface $instance,$id) {
        $this->assertGranted('ad.get', $instance);
        $adPage = $this->getClassResolver()->resolve('Ads\Entity\AdPageInterface');
        $adPage->setInstance($instance);
        $repository = $this->getPageManager()->getPageRepository($id);
        $adPage->setPageRepository($repository);
        return $adPage;    
    }
    
    public function createAdPage(InstanceInterface $instance)
    {
        $this->assertGranted('ad.get', $instance);
        $adPage = $this->getClassResolver()->resolve('Ads\Entity\AdPageInterface');
        $adPage->setInstance($instance);
        $repository = $this->getPageManager()->createPageRepository(array(
            'instance' => $instance,
            'roles' => array(
                6
            )
        ), $instance);
        $adPage->setPageRepository($repository);
        return $adPage;
    }

    public function getAdPage(InstanceInterface $instance)
    {
        $this->assertGranted('ad.get', $instance);
        $className = $this->getClassResolver()->resolveClassName('Ads\Entity\AdPageInterface');
        $adPage = $this->getObjectManager()
            ->getRepository($className)
            ->findOneBy(array(
            'instance' => $instance
        ));
        if (! is_object($adPage)) {
            return null;
        }
        if (! is_object($adPage->getPageRepository()) || $adPage->getPageRepository()->isTrashed()) {
            $this->getObjectManager()->remove($adPage);
            return null;
        }
        return $adPage;
    }

    public function findShuffledAds(InstanceInterface $instance, $number)
    {
        $sql = 'SELECT * FROM ad WHERE `instance_id` =' . (int)$instance->getId() . ' ORDER BY RAND( ) * frequency DESC LIMIT ' . (int)$number;
        $stmt = $this->getObjectManager()
            ->getConnection()
            ->prepare($sql);
        $stmt->execute();
        
        $adArray = $stmt->fetchAll();
        $adCollection = array();
        
        $className = $this->getClassResolver()->resolveClassName('Ads\Entity\AdInterface');
        
        foreach ($adArray as $ad) {
            $addCollection[] = $this->getObjectManager()
                ->getRepository($className)
                ->find($ad['id']);
        }
        if (! empty($addCollection)) {
            return $addCollection;
        } else {
            return null;
        }
    }

    public function flush()
    {
        $this->getObjectManager()->flush();
    }

    public function getAd($id)
    {
        if (! is_numeric($id)) {
            throw new InvalidArgumentException(sprintf('Expected numeric but got %s', gettype($id)));
        }
        
        $className = $this->getClassResolver()->resolveClassName('Ads\Entity\AdInterface');
        $ad = $this->getObjectManager()->find($className, $id);
        $this->assertGranted('ad.get', $ad);
        
        if (! $ad) {
            throw new AdNotFoundException(sprintf('%s', $id));
        }
        
        return $ad;
    }

    public function removeAd(AdInterface $ad)
    {
        $this->assertGranted('ad.remove', $ad);
        $this->getObjectManager()->remove($ad);
    }

    public function updateAd(array $data, AdInterface $ad)
    {
        $this->assertGranted('ad.update', $ad);
        $hydrator = new AdHydrator();
        $hydrator->hydrate($data, $ad);
        $this->getObjectManager()->persist($ad);
        return $ad;
    }

    protected function createAdEntity()
    {
        $ad = $this->getClassResolver()->resolve('Ads\Entity\AdInterface');
        return $ad;
    }
}
