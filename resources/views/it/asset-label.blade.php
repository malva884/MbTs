<div class="hero-image">
  <div class="label-row">
    <div class="label-left">
      <img src="/images/custom/logo_stl.jpg" alt="Logo STL" class="logo-stl" />
      <p class="serial-text">S.no {{ $asset->serial_number ?? 'N/A' }}</p>
      <p class="asset-text">ITALY {{ $asset->asset_tag ?? 'N/A' }}</p>
    </div>
    <div class="label-right">
      <img src="https://api.qrserver.com/v1/create-qr-code/?size=90x90&data={{ $asset->serial_number ?? $asset->asset_tag ?? $asset->id }}" alt="QR Code" class="qr-code" />
    </div>
  </div>
  <div class="label-footer">
    <p class="phone-text">+39 - 030 - 9771911</p>
    <img src="/images/custom/logo_mb.png" alt="Logo MB" class="logo-mb" />
  </div>
</div>
