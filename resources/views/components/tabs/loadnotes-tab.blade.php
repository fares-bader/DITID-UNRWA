@props(['count' => 0])

<li class="nav-item">
    <a href="#loadnotes" data-toggle="tab" class="nav-link" data-tooltip="true" title="Load Notes">
        <i class="fas fa-file-invoice fa-fw"></i>
        <span class="sr-only">Load Notes</span>
        @if((int)$count > 0)
            <span class="badge badge-secondary">{{ $count }}</span>
        @endif
    </a>
</li>