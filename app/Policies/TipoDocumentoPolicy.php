<?php

namespace App\Policies;

use App\Models\TipoDocumento;
use App\Models\User;

/**
 * Policy del módulo tipos-documentos.
 */
class TipoDocumentoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'tipos-documentos';
}
