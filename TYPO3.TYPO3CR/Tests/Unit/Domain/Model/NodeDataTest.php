<?php
namespace TYPO3\TYPO3CR\Tests\Unit\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Security\Context;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;

/**
 * Test case for the "NodeData" domain model
 */
class NodeDataTest extends UnitTestCase {

	/**
	 * @var Workspace|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockWorkspace;

	/**
	 * @var NodeTypeManager|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockNodeTypeManager;

	/**
	 * @var NodeType
	 */
	protected $mockNodeType;

	/**
	 * @var NodeDataRepository|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockNodeDataRepository;

	/**
	 * @var NodeData|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $nodeData;

	public function setUp() {
		$this->mockWorkspace = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);
		$this->nodeData = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array('addOrUpdate'), array('/foo/bar', $this->mockWorkspace));

		$this->mockNodeType = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeType')->disableOriginalConstructor()->getMock();

		$this->mockNodeTypeManager = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager')->disableOriginalConstructor()->getMock();
		$this->mockNodeTypeManager->expects($this->any())->method('getNodeType')->will($this->returnValue($this->mockNodeType));
		$this->inject($this->nodeData, 'nodeTypeManager', $this->mockNodeTypeManager);

		$this->mockNodeDataRepository = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository')->disableOriginalConstructor()->getMock();
		$this->inject($this->nodeData, 'nodeDataRepository', $this->mockNodeDataRepository);
	}

	/**
	 * @test
	 */
	public function constructorSetsPathWorkspaceAndIdentifier() {
		$node = new NodeData('/foo/bar', $this->mockWorkspace, '12345abcde');
		$this->assertSame('/foo/bar', $node->getPath());
		$this->assertSame('bar', $node->getName());
		$this->assertSame($this->mockWorkspace, $node->getWorkspace());
		$this->assertSame('12345abcde', $node->getIdentifier());
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @dataProvider invalidPaths()
	 */
	public function setPathThrowsAnExceptionIfAnInvalidPathIsPassed($path) {
		$this->nodeData->_call('setPath', $path, FALSE);
	}

	/**
	 */
	public function invalidPaths() {
		return array(
			array('foo'),
			array('/ '),
			array('//'),
			array('/foo//bar'),
			array('/foo/ bar'),
			array('/foo/bar/'),
			array('/123 bar'),
		);
	}

	/**
	 * @test
	 * @dataProvider validPaths()
	 */
	public function setPathAcceptsAValidPath($path) {
		$this->nodeData->_call('setPath', $path, FALSE);
			// dummy assertion to avoid PHPUnit warning in strict mode
		$this->assertTrue(TRUE);
	}

	/**
	 */
	public function validPaths() {
		return array(
			array('/foo'),
			array('/foo/bar'),
			array('/foo/bar/baz'),
			array('/12/foo'),
			array('/12356'),
			array('/foo-bar'),
			array('/foo-bar/1-5'),
			array('/foo-bar/bar/asdkak/dsflasdlfkjasd/asdflnasldfkjalsd/134-111324823-234234-234/sdasdflkj'),
		);
	}

	/**
	 * @test
	 */
	public function getDepthReturnsThePathDepthOfTheNode() {
		$node = new NodeData('/', $this->mockWorkspace);
		$this->assertEquals(0, $node->getDepth());

		$node = new NodeData('/foo', $this->mockWorkspace);
		$this->assertEquals(1, $node->getDepth());

		$node = new NodeData('/foo/bar', $this->mockWorkspace);
		$this->assertEquals(2, $node->getDepth());

		$node = new NodeData('/foo/bar/baz/quux', $this->mockWorkspace);
		$this->assertEquals(4, $node->getDepth());
	}

	/**
	 * @test
	 */
	public function setWorkspacesAllowsForSettingTheWorkspaceForInternalPurposes() {
		/** @var Workspace|\PHPUnit_Framework_MockObject_MockObject $newWorkspace */
		$newWorkspace = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);

		$this->assertSame($this->mockWorkspace, $this->nodeData->getWorkspace());

		$this->nodeData->setWorkspace($newWorkspace);
		$this->assertSame($newWorkspace, $this->nodeData->getWorkspace());
	}

	/**
	 * @test
	 */
	public function theIndexCanBeSetAndRetrieved() {
		$this->nodeData->setIndex(2);
		$this->assertEquals(2, $this->nodeData->getIndex());
	}

	/**
	 * @test
	 */
	public function getParentReturnsNullForARootNode() {
		$node = new NodeData('/', $this->mockWorkspace);
		$this->assertNull($node->getParent());
	}

	/**
	 * @test
	 */
	public function aContentObjectCanBeSetRetrievedAndUnset() {
		$contentObject = new \stdClass();

		$this->nodeData->setContentObject($contentObject);
		$this->assertSame($contentObject, $this->nodeData->getContentObject());

		$this->nodeData->unsetContentObject();
		$this->assertNull($this->nodeData->getContentObject());
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function aContentObjectMustBeAnObject() {
		$this->nodeData->setContentObject('not an object');
	}

	/**
	 * @test
	 */
	public function propertiesCanBeSetAndRetrieved() {
		$this->nodeData->setProperty('title', 'My Title');
		$this->nodeData->setProperty('body', 'My Body');

		$this->assertTrue($this->nodeData->hasProperty('title'));
		$this->assertFalse($this->nodeData->hasProperty('iltfh'));

		$this->assertEquals('My Body', $this->nodeData->getProperty('body'));
		$this->assertEquals('My Title', $this->nodeData->getProperty('title'));

		$this->assertEquals(array('title' => 'My Title', 'body' => 'My Body'), $this->nodeData->getProperties());

		$actualPropertyNames = $this->nodeData->getPropertyNames();
		sort($actualPropertyNames);
		$this->assertEquals(array('body', 'title'), $actualPropertyNames);

	}

	/**
	 * @test
	 */
	public function propertiesCanBeRemoved() {
		$this->nodeData->setProperty('title', 'My Title');
		$this->assertTrue($this->nodeData->hasProperty('title'));

		$this->nodeData->removeProperty('title');

		$this->assertFalse($this->nodeData->hasProperty('title'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\TYPO3CR\Exception\NodeException
	 */
	public function removePropertyThrowsExceptionIfPropertyDoesNotExist() {
		$this->nodeData->removeProperty('nada');
	}

	/**
	 * @test
	 */
	public function removePropertyDoesNotTouchAContentObject() {
		$this->inject($this->nodeData, 'persistenceManager', $this->getMock('TYPO3\Flow\Persistence\PersistenceManagerInterface'));

		$className = uniqid('Test');
		eval('class ' .$className . ' {
				public $title = "My Title";
			}');
		$contentObject = new $className();
		$this->nodeData->setContentObject($contentObject);

		$this->nodeData->removeProperty('title');

		$this->assertTrue($this->nodeData->hasProperty('title'));
		$this->assertEquals('My Title', $this->nodeData->getProperty('title'));
	}

	/**
	 * @test
	 */
	public function propertyFunctionsUseAContentObjectIfOneHasBeenDefined() {
		$this->inject($this->nodeData, 'persistenceManager', $this->getMock('TYPO3\Flow\Persistence\PersistenceManagerInterface'));

		$className = uniqid('Test');
		eval('
			class ' .$className . ' {
				public $title = "My Title";
				public $body = "My Body";
			}
		');
		$contentObject = new $className;

		$this->nodeData->setContentObject($contentObject);

		$this->assertTrue($this->nodeData->hasProperty('title'));
		$this->assertFalse($this->nodeData->hasProperty('iltfh'));

		$this->assertEquals('My Body', $this->nodeData->getProperty('body'));
		$this->assertEquals('My Title', $this->nodeData->getProperty('title'));

		$this->assertEquals(array('title' => 'My Title', 'body' => 'My Body'), $this->nodeData->getProperties());

		$actualPropertyNames = $this->nodeData->getPropertyNames();
		sort($actualPropertyNames);
		$this->assertEquals(array('body', 'title'), $actualPropertyNames);

		$this->nodeData->setProperty('title', 'My Other Title');
		$this->nodeData->setProperty('body', 'My Other Body');

		$this->assertEquals('My Other Body', $this->nodeData->getProperty('body'));
		$this->assertEquals('My Other Title', $this->nodeData->getProperty('title'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\TYPO3CR\Exception\NodeException
	 */
	public function getPropertyThrowsAnExceptionIfTheSpecifiedPropertyDoesNotExistInTheContentObject() {
		$className = uniqid('Test');
		eval('
			class ' .$className . ' {
				public $title = "My Title";
			}
		');
		$contentObject = new $className;
		$this->nodeData->setContentObject($contentObject);

		$this->nodeData->getProperty('foo');
	}

	/**
	 * @test
	 */
	public function theNodeTypeCanBeSetAndRetrieved() {
		/** @var NodeTypeManager|\PHPUnit_Framework_MockObject_MockObject $mockNodeTypeManager */
		$mockNodeTypeManager = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager')->disableOriginalConstructor()->getMock();
		$mockNodeTypeManager->expects($this->any())->method('getNodeType')->will($this->returnCallback(
			function ($name) {
				return new NodeType($name, array(), array()) ;
			}
		));

		$this->inject($this->nodeData, 'nodeTypeManager', $mockNodeTypeManager);

		$this->assertEquals('unstructured', $this->nodeData->getNodeType()->getName());

		$myNodeType = $mockNodeTypeManager->getNodeType('typo3:mycontent');
		$this->nodeData->setNodeType($myNodeType);
		$this->assertEquals($myNodeType, $this->nodeData->getNodeType());
	}

	/**
	 * @test
	 */
	public function createNodeCreatesAChildNodeOfTheCurrentNodeInTheContextWorkspace() {
		$this->marktestIncomplete('Should be refactored to a contextualized node test.');

		$context = $this->getMock('TYPO3\TYPO3CR\Domain\Service\Context', array(), array(), '', FALSE);
		$context->expects($this->once())->method('getWorkspace')->will($this->returnValue($this->mockWorkspace));

		$nodeDataRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository', array('countByParentAndNodeType', 'add'), array(), '', FALSE);
		$nodeDataRepository->expects($this->once())->method('countByParentAndNodeType')->with('/', NULL, $this->mockWorkspace)->will($this->returnValue(0));
		$nodeDataRepository->expects($this->once())->method('add');
		$nodeDataRepository->expects($this->any())->method('getContext')->will($this->returnValue($context));

		/** @var NodeData|\PHPUnit_Framework_MockObject_MockObject $currentNode */
		$currentNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array('getNode'), array('/', $this->mockWorkspace));
		$this->inject($currentNode, 'nodeDataRepository', $nodeDataRepository);

		$currentNode->expects($this->once())->method('createProxyForContextIfNeeded')->will($this->returnArgument(0));
		$currentNode->expects($this->once())->method('filterNodeByContext')->will($this->returnArgument(0));

		$newNode = $currentNode->createNode('foo', 'mynodetype');
		$this->assertSame($currentNode, $newNode->getParent());
		$this->assertEquals(1, $newNode->getIndex());
		$this->assertEquals('mynodetype', $newNode->getNodeType()->getName());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\TYPO3CR\Exception\NodeException
	 */
	public function createNodeThrowsNodeExceptionIfPathAlreadyExists() {
		$mockContext = $this->getMock('TYPO3\TYPO3CR\Domain\Service\Context', array(), array(), '', FALSE);
		$mockContext->expects($this->any())->method('getWorkspace')->will($this->returnValue($this->mockWorkspace));

		$oldNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array(), array('/foo', $this->mockWorkspace));

		$nodeDataRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository', array('findOneByPath', 'getContext'), array(), '', FALSE);
		$nodeDataRepository->expects($this->any())->method('findOneByPath')->with('/foo', $this->mockWorkspace)->will($this->returnValue($oldNode));

		/** @var NodeData|\PHPUnit_Framework_MockObject_MockObject $currentNode */
		$currentNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array('getNode'), array('/', $this->mockWorkspace));
		$this->inject($currentNode, 'nodeDataRepository', $nodeDataRepository);
		$currentNode->_set('context', $mockContext);

		$currentNode->createNodeData('foo');
	}

	/**
	 * @test
	 */
	public function getNodeReturnsNullIfTheSpecifiedNodeDoesNotExist() {
		$nodeDataRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository', array('findOneByPath', 'getContext'), array(), '', FALSE);
		$nodeDataRepository->expects($this->once())->method('findOneByPath')->with('/foo/quux', $this->mockWorkspace)->will($this->returnValue(NULL));

		$currentNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array('normalizePath', 'getContext'), array('/foo/baz', $this->mockWorkspace));
		$this->inject($currentNode, 'nodeDataRepository', $nodeDataRepository);
		$currentNode->expects($this->once())->method('normalizePath')->with('/foo/quux')->will($this->returnValue('/foo/quux'));

		$this->assertNull($currentNode->getNode('/foo/quux'));
	}

	/**
	 * @test
	 */
	public function getChildNodeDataFindsUnreducedNodeDataChildren() {
		$childNodeData = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array(), array('/foo/bar', $this->mockWorkspace));
		$nodeType = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeType', array(), array('mynodetype', array(), array()));
		$childNodeData->setNodeType($nodeType);
		$childNodeDataResults = array(
			$childNodeData
		);

		$nodeDataRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository', array(), array(), '', FALSE);
		$nodeDataRepository->expects($this->at(0))->method('findByParentWithoutReduce')->with('/foo', $this->mockWorkspace)->will($this->returnValue($childNodeDataResults));
		$nodeDataRepository->expects($this->at(1))->method('findByParentWithoutReduce')->with('/foo', $this->mockWorkspace)->will($this->returnValue(array()));

		$nodeData = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array('dummy'), array('/foo', $this->mockWorkspace));
		$this->inject($nodeData, 'nodeDataRepository', $nodeDataRepository);

		$this->assertSame($childNodeDataResults, $nodeData->_call('getChildNodeData', 'mynodetype'));
		$this->assertSame(array(), $nodeData->_call('getChildNodeData', 'notexistingnodetype'));
	}


	/**
	 * @test
	 */
	public function removeOnlyFlagsTheNodeAsRemovedIfItsWorkspaceHasAnotherBaseWorkspace() {
		$mockPersistenceManager = $this->getMock('TYPO3\Flow\Persistence\PersistenceManagerInterface');

		$baseWorkspace = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);

		$workspace = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);
		$workspace->expects($this->once())->method('getBaseWorkspace')->will($this->returnValue($baseWorkspace));

		$nodeDataRepository = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository', array('remove', 'update'), array(), '', FALSE);
		$this->inject($nodeDataRepository, 'entityClassName', 'TYPO3\TYPO3CR\Domain\Model\NodeData');
		$this->inject($nodeDataRepository, 'persistenceManager', $mockPersistenceManager);

		$currentNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array('addOrUpdate'), array('/foo', $workspace));
		$this->inject($currentNode, 'nodeDataRepository', $nodeDataRepository);

		$nodeDataRepository->expects($this->never())->method('remove');

		$currentNode->remove();

		$this->assertTrue($currentNode->isRemoved());
	}

	/**
	 * @test
	 */
	public function removeRemovesTheNodeFromRepositoryIfItsWorkspaceHasNoOtherBaseWorkspace() {
		$mockPersistenceManager = $this->getMock('TYPO3\Flow\Persistence\PersistenceManagerInterface');

		$workspace = $this->getMock('TYPO3\TYPO3CR\Domain\Model\Workspace', array(), array(), '', FALSE);
		$workspace->expects($this->once())->method('getBaseWorkspace')->will($this->returnValue(NULL));

		$nodeDataRepository = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository', array('remove', 'update'), array(), '', FALSE);
		$this->inject($nodeDataRepository, 'entityClassName', 'TYPO3\TYPO3CR\Domain\Model\NodeData');
		$this->inject($nodeDataRepository, 'persistenceManager', $mockPersistenceManager);

		$currentNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', NULL, array('/foo', $workspace));
		$this->inject($currentNode, 'nodeDataRepository', $nodeDataRepository);

		$nodeDataRepository->expects($this->once())->method('remove');

		$currentNode->remove();
	}

	/**
	 * @test
	 */
	public function setRemovedCallsRemoveMethodIfArgumentIsTrue() {
		$node = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array('remove'), array(), '', FALSE);
		$node->expects($this->once())->method('remove');
		$node->setRemoved(TRUE);
	}

	/**
	 * @param string $currentPath
	 * @param string $relativePath
	 * @param string $normalizedPath
	 * @test
	 * @dataProvider abnormalPaths
	 */
	public function normalizePathReturnsANormalizedAbsolutePath($currentPath, $relativePath, $normalizedPath) {
		$this->nodeData->_set('path', $currentPath);
		$this->assertSame($normalizedPath, $this->nodeData->_call('normalizePath', $relativePath));
	}

	/**
	 * @return array
	 */
	public function abnormalPaths() {
		return array(
			array('/', '/', '/'),
			array('/', '/.', '/'),
			array('/', '.', '/'),
			array('/', 'foo/bar', '/foo/bar'),
			array('/foo', '.', '/foo'),
			array('/foo', '/foo/.', '/foo'),
			array('/foo', '../', '/'),
			array('/foo/bar', '../baz', '/foo/baz'),
			array('/foo/bar', '../baz/../bar', '/foo/bar'),
			array('/foo/bar', '.././..', '/'),
			array('/foo/bar', '../../.', '/'),
			array('/foo/bar/baz', '../..', '/foo'),
			array('/foo/bar/baz', '../quux', '/foo/bar/quux'),
			array('/foo/bar/baz', '../quux/.', '/foo/bar/quux')
		);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function normalizePathThrowsInvalidArgumentExceptionOnPathContainingDoubleSlash() {
		$node = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array('dummy'), array(), '', FALSE);
		$node->_call('normalizePath', 'foo//bar');
	}

	/**
	 * @test
	 */
	public function isAccessibleReturnsTrueIfAccessRolesIsNotSet() {
		$this->assertTrue($this->nodeData->isAccessible());
	}

	/**
	 * @test
	 */
	public function isAccessibleReturnsFalseIfAccessRolesIsSetAndSecurityContextHasNoRoles() {
		/** @var Context|\PHPUnit_Framework_MockObject_MockObject $mockSecurityContext */
		$mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context');
		$mockSecurityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(TRUE));
		$mockSecurityContext->expects($this->any())->method('hasRole')->will($this->returnValue(FALSE));
		$this->inject($this->nodeData, 'securityContext', $mockSecurityContext);

		$this->nodeData->setAccessRoles(array('SomeRole'));
		$this->assertFalse($this->nodeData->isAccessible());
	}

	/**
	 * @test
	 */
	public function isAccessibleReturnsTrueIfAccessRolesIsSetAndSecurityContextHasOneOfTheRequiredRoles() {
		/** @var Context|\PHPUnit_Framework_MockObject_MockObject $mockSecurityContext */
		$mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context');
		$mockSecurityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(TRUE));
		$mockSecurityContext->expects($this->at(1))->method('hasRole')->with('SomeRole')->will($this->returnValue(FALSE));
		$mockSecurityContext->expects($this->at(2))->method('hasRole')->with('SomeOtherRole')->will($this->returnValue(TRUE));
		$this->inject($this->nodeData, 'securityContext', $mockSecurityContext);

		$this->nodeData->setAccessRoles(array('SomeRole', 'SomeOtherRole'));
		$this->assertTrue($this->nodeData->isAccessible());
	}

	/**
	 * @test
	 */
	public function isAccessibleReturnsTrueIfRoleIsEveryone() {
		/** @var Context|\PHPUnit_Framework_MockObject_MockObject $mockSecurityContext */
		$mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context');
		$mockSecurityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(TRUE));
		$mockSecurityContext->expects($this->at(1))->method('hasRole')->with('SomeRole')->will($this->returnValue(FALSE));
		$mockSecurityContext->expects($this->at(2))->method('hasRole')->with('Everyone')->will($this->returnValue(TRUE));
		$this->inject($this->nodeData, 'securityContext', $mockSecurityContext);

		$this->nodeData->setAccessRoles(array('SomeRole', 'Everyone', 'SomeOtherRole'));
		$this->assertTrue($this->nodeData->isAccessible());
	}

	/**
	 * @test
	 */
	public function createNodeCreatesNodeDataWithExplicitWorkspaceIfGiven() {
		/** @var NodeDataRepository|\PHPUnit_Framework_MockObject_MockObject $nodeDataRepository */
		$nodeDataRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository');
		$this->inject($this->nodeData, 'nodeDataRepository', $nodeDataRepository);

		$nodeDataRepository->expects($this->atLeastOnce())->method('setNewIndex');

		$this->nodeData->createNodeData('foo', NULL, NULL, $this->mockWorkspace);
	}

	/**
	 * @test
	 */
	public function similarizeClearsPropertiesBeforeAddingNewOnes() {
		/** @var $sourceNode NodeData */
		$sourceNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array('addOrUpdate'), array('/foo/bar', $this->mockWorkspace));
		$this->inject($sourceNode, 'nodeTypeManager', $this->mockNodeTypeManager);
		$sourceNode->_set('nodeDataRepository', $this->getMock('TYPO3\Flow\Persistence\RepositoryInterface'));

		$this->nodeData->setProperty('someProperty', 'somePropertyValue');
		$this->nodeData->setProperty('someOtherProperty', 'someOtherPropertyValue');

		$sourceNode->setProperty('newProperty', 'newPropertyValue');
		$sourceNode->setProperty('someProperty', 'someOverriddenPropertyValue');
		$this->nodeData->similarize($sourceNode);

		$expectedProperties = array(
			'newProperty' => 'newPropertyValue',
			'someProperty' => 'someOverriddenPropertyValue'
		);
		$this->assertEquals($expectedProperties, $this->nodeData->getProperties());
	}

	/**
	 * @test
	 */
	public function matchesWorkspaceAndDimensionsWithDifferentWorkspaceReturnsFalse() {
		$this->mockWorkspace->expects($this->any())->method('getName')->will($this->returnValue('live'));

		/** @var Workspace|\PHPUnit_Framework_MockObject_MockObject $otherWorkspace */
		$otherWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
		$otherWorkspace->expects($this->any())->method('getName')->will($this->returnValue('other'));

		$result = $this->nodeData->matchesWorkspaceAndDimensions($otherWorkspace, NULL);
		$this->assertFalse($result);
	}

	/**
	 * @test
	 */
	public function matchesWorkspaceAndDimensionsWithDifferentDimensionReturnsFalse() {
		$this->nodeData = new NodeData('/foo/bar', $this->mockWorkspace, NULL, array('language' => array('en_US')));

		$this->mockWorkspace->expects($this->any())->method('getName')->will($this->returnValue('live'));

		$result = $this->nodeData->matchesWorkspaceAndDimensions($this->mockWorkspace, array('language' => array('de_DE', 'mul_ZZ')));
		$this->assertFalse($result);
	}

	/**
	 * @test
	 */
	public function matchesWorkspaceAndDimensionsWithMatchingWorkspaceAndDimensionsReturnsTrue() {
		$this->nodeData = new NodeData('/foo/bar', $this->mockWorkspace, NULL, array('language' => array('mul_ZZ')));

		$this->mockWorkspace->expects($this->any())->method('getName')->will($this->returnValue('live'));

		$result = $this->nodeData->matchesWorkspaceAndDimensions($this->mockWorkspace, array('language' => array('de_DE', 'mul_ZZ')));
		$this->assertTrue($result);
	}

	/**
	 * @test
	 */
	public function getDimensionValuesReturnsDimensionsSortedByKey() {
		$nodeData = new NodeData('/foo/bar', $this->mockWorkspace, NULL, array('c' => array('c1', 'c2'), 'a' => array('a1')));
		$dimensionValues = $nodeData->getDimensionValues();

		$this->assertSame(array('a' => array('a1'), 'c' => array('c1', 'c2')), $dimensionValues);
	}

	/**
	 * @test
	 */
	public function dimensionsHashIsOrderIndependent() {
		$nodeData = new NodeData('/foo/bar', $this->mockWorkspace, NULL, array('c' => array('c1', 'c2'), 'a' => array('a1')));
		$dimensionsHash = $nodeData->getDimensionsHash();

		$this->assertSame('955c716a191a0957f205ea9376600e72', $dimensionsHash);

		$nodeData = new NodeData('/foo/bar', $this->mockWorkspace, NULL, array('a' => array('a1'), 'c' => array('c2', 'c1')));
		$dimensionsHash = $nodeData->getDimensionsHash();

		$this->assertSame('955c716a191a0957f205ea9376600e72', $dimensionsHash);
	}

}
