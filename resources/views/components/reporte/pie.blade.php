@props(['nota' => null])
<div class="reporte-pie" style="position:fixed; bottom:0; width:100%; text-align:center; font-size:7pt; color:#999; border-top:1px solid #ddd; padding-top:5px;">
    {{ $nota ?? 'EMCARGA © '.date('Y').' | Página {PAGE_NUM} de {PAGE_COUNT}' }}
</div>
