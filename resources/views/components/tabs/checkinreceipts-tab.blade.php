@props(['count' => 0])

<li class="nav-item">
    <a href="#checkinreceipts" data-toggle="tab" class="nav-link" data-tooltip="true" title="Return Receipts">
        <i class="fas fa-file-signature fa-fw"></i>
        <span class="sr-only">Return Receipts</span>
        @if((int)$count > 0)
            <span class="badge badge-secondary">{{ $count }}</span>
        @endif
    </a>
</li>