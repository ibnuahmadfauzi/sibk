<footer class="sibk-document-signatures">
    <div class="sibk-document-signatures__column">
        <p class="sibk-document-signatures__role">
            Mengetahui,<br>
            {{ $signatories['left']['role'] }}
        </p>
        <p class="sibk-document-signatures__name">
            {{ $signatories['left']['name'] }}
        </p>
    </div>
    <div class="sibk-document-signatures__column">
        <p class="sibk-document-signatures__role">
            Surabaya, {{ $generatedAt->locale('id')->translatedFormat('d F Y') }}<br>
            {{ $signatories['right']['role'] }}
        </p>
        <p class="sibk-document-signatures__name">
            {{ $signatories['right']['name'] }}
        </p>
    </div>
</footer>
