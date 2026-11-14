<form method="POST" action="{{ route('public.reviews.store') }}" id="reviewForm">
    @csrf
    <p class="small text-muted" id="review-privacy">Your review will appear after approval. Your name, optional location and feedback will be public. Please avoid private medical details.</p>
    @if($errors->review->any())
        <div class="alert alert-danger" role="alert" tabindex="-1" id="review-errors"><strong>Please check your review:</strong><ul class="mb-0">@foreach($errors->review->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <div class="mb-3"><label class="form-label" for="review-name">Your name <span aria-hidden="true">*</span></label><input class="form-control @error('name','review') is-invalid @enderror" id="review-name" name="name" maxlength="120" required autocomplete="name" value="{{ old('name') }}" @error('name','review') aria-invalid="true" @enderror></div>
    <div class="mb-3"><label class="form-label" for="review-workplace">Location or workplace <span class="text-muted">(optional)</span></label><input class="form-control @error('workplace','review') is-invalid @enderror" id="review-workplace" name="workplace" maxlength="160" value="{{ old('workplace') }}"></div>
    <div class="mb-3"><label class="form-label" for="review-rating">Rating <span aria-hidden="true">*</span></label><select class="form-select @error('rating','review') is-invalid @enderror" id="review-rating" name="rating" required><option value="">Choose a rating</option>@foreach([5=>'5 — Excellent',4=>'4 — Good',3=>'3 — Average',2=>'2 — Fair',1=>'1 — Poor'] as $value=>$label)<option value="{{ $value }}" @selected((string)old('rating') === (string)$value)>{{ $label }}</option>@endforeach</select></div>
    <div class="mb-3"><label class="form-label" for="review-message">Your review <span aria-hidden="true">*</span></label><textarea class="form-control @error('message','review') is-invalid @enderror" id="review-message" name="message" rows="4" minlength="10" maxlength="3000" required aria-describedby="review-privacy">{{ old('message') }}</textarea><span class="small text-muted">10–3,000 characters.</span></div>
    <div class="review-trap" aria-hidden="true"><label for="review-contact-fax">Leave this field empty</label><input id="review-contact-fax" name="contact_fax" tabindex="-1" autocomplete="off" value=""></div>
    <div class="form-check mb-4"><input class="form-check-input" type="checkbox" id="review-consent" name="consent" value="1" required @checked(old('consent'))><label class="form-check-label small" for="review-consent">I agree to publish my name, location (if supplied), rating and review after approval.</label></div>
    <button class="btn btn-success w-100" type="submit" id="review-submit">Submit review</button>
</form>
