<?php

declare(strict_types=1);

namespace Nusje2000\DependencyGraph\Validator\Rule;

use Composer\Semver\VersionParser;
use Nusje2000\DependencyGraph\DependencyGraph;
use Nusje2000\DependencyGraph\Validator\RuleInterface;
use Nusje2000\DependencyGraph\Validator\Violation\IncompatibleVersionConstraintViolation;
use Nusje2000\DependencyGraph\Validator\ViolationCollection;

final class IncompatibleVersionRule implements RuleInterface
{
    /**
     * @var VersionParser
     */
    private $versionParser;

    public function __construct()
    {
        $this->versionParser = new VersionParser();
    }

    public function execute(DependencyGraph $graph): ViolationCollection
    {
        $rootPackage = $graph->getRootPackage();
        $subPackages = $graph->getSubPackages();
        $violations = new ViolationCollection();

        foreach ($subPackages as $subPackage) {
            foreach ($subPackage->getDependencies() as $dependency) {
                if (!$rootPackage->hasDependency($dependency->getName())) {
                    continue;
                }

                $rootDependency = $rootPackage->getDependency($dependency->getName());
                if (!$this->isCompatible($rootDependency->getVersionConstraint(), $dependency->getVersionConstraint())) {
                    $violations->append(
                        new IncompatibleVersionConstraintViolation($subPackage, $dependency)
                    );
                }
            }
        }

        return $violations;
    }

    private function isCompatible(string $rootVersionConstraint, string $subPackageVersionConstraint): bool
    {
        return $this->versionParser->parseConstraints($subPackageVersionConstraint)->matches(
            $this->versionParser->parseConstraints($rootVersionConstraint)
        );
    }
}
