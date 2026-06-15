<?php

namespace GlpiPlugin\Torah\Infrastructure\Glpi;

use GlpiPlugin\Torah\Application\ActorMutationDetector;
use GlpiPlugin\Torah\Application\CapabilityRegistry;
use GlpiPlugin\Torah\Application\FieldMutationDetector;
use GlpiPlugin\Torah\Application\InternalMutationClassifier;
use GlpiPlugin\Torah\Application\PolicyCatalog;
use GlpiPlugin\Torah\Application\PolicyResolver;
use GlpiPlugin\Torah\Application\TicketMutationGuard;
use Plugin;

final class ServiceFactory
{
   private static bool $capabilitiesLoaded = false;
   private static ?PolicyCatalog $catalog = null;
   private static ?PolicyResolver $resolver = null;
   private static ?TicketMutationGuard $guard = null;

   public static function catalog(): PolicyCatalog {
       self::loadExternalCapabilities();

       return self::$catalog ??= new PolicyCatalog(CapabilityRegistry::instance());
   }

   public static function resolver(): PolicyResolver {
       return self::$resolver ??= new PolicyResolver(
           new GlpiPolicyRepository(),
           new GlpiEntityHierarchy(),
       );
   }

   public static function guard(): TicketMutationGuard {
       return self::$guard ??= new TicketMutationGuard(
           self::resolver(),
           self::catalog(),
           new AuthorizationContextFactory(),
           new ActorMutationDetector(),
           new FieldMutationDetector(),
           new InternalMutationClassifier(),
           new GlpiAuditLogger(),
       );
   }

   private static function loadExternalCapabilities(): void {
      if (self::$capabilitiesLoaded) {
          return;
      }

       self::$capabilitiesLoaded = true;
       Plugin::doHook('torah_register_capabilities', [
           'registry' => CapabilityRegistry::instance(),
       ]);
   }
}
