@auth
    <form method="POST"
          action="{{ $isFavorited ? route('wishlist.destroy', $car) : route('wishlist.store', $car) }}"
          class="wishlist-toggle-form {{ $class ?? '' }}"
          data-wishlist-toggle
          data-wishlist-car-id="{{ $car->id }}"
          data-store-url="{{ route('wishlist.store', $car) }}"
          data-destroy-url="{{ route('wishlist.destroy', $car) }}"
          onclick="event.stopPropagation()">
        @csrf

        @if($isFavorited)
            @method('DELETE')
        @endif

        <button type="submit"
                class="wishlist-toggle-btn {{ $isFavorited ? 'wishlist-toggle-btn--active' : '' }}"
                title="{{ $isFavorited ? __('messages.wishlist_remove') : __('messages.wishlist_add') }}"
                aria-label="{{ $isFavorited ? __('messages.wishlist_remove') : __('messages.wishlist_add') }}"
                data-wishlist-button
                data-add-label="{{ __('messages.wishlist_add') }}"
                data-remove-label="{{ __('messages.wishlist_remove') }}"
                onclick="event.stopPropagation()">
            <i class="{{ $isFavorited ? 'fa-solid' : 'fa-regular' }} fa-heart" aria-hidden="true" data-wishlist-icon></i>
        </button>
    </form>
@endauth

@once
    <style>
        .wishlist-toggle-form {
            display: inline-flex;
            margin: 0;
        }

        .wishlist-toggle-form--overlay {
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 4;
        }

        .wishlist-toggle-form--inline {
            flex-shrink: 0;
        }

        .wishlist-toggle-btn {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 999px;
            background: transparent;
            color: rgba(255,255,255,0.86);
            font-size: 1.15rem;
            line-height: 1;
            cursor: pointer;
            box-shadow: none;
            transition: transform 0.2s ease, background-color 0.2s ease, color 0.2s ease, opacity 0.2s ease;
        }

        .wishlist-toggle-btn:hover,
        .wishlist-toggle-btn:focus-visible {
            transform: scale(1.08);
            background-color: rgba(255,255,255,0.10);
            color: #fff;
            outline: none;
        }

        .wishlist-toggle-btn--active {
            background: transparent;
            color: #ef4444;
        }

        .wishlist-toggle-btn--active:hover,
        .wishlist-toggle-btn--active:focus-visible {
            background-color: rgba(239,68,68,0.10);
            color: #dc2626;
        }

        .wishlist-toggle-btn:disabled {
            cursor: wait;
            opacity: 0.72;
        }

        [dir="rtl"] .wishlist-toggle-form--overlay {
            right: auto;
            left: 12px;
        }
    </style>

    @push('scripts')
        <script>
            (function () {
                function setWishlistState(form, isActive) {
                    const button = form.querySelector('[data-wishlist-button]');
                    const icon = form.querySelector('[data-wishlist-icon]');
                    const addLabel = button.dataset.addLabel;
                    const removeLabel = button.dataset.removeLabel;
                    let methodInput = form.querySelector('input[name="_method"]');

                    form.action = isActive ? form.dataset.destroyUrl : form.dataset.storeUrl;
                    button.classList.toggle('wishlist-toggle-btn--active', isActive);
                    button.title = isActive ? removeLabel : addLabel;
                    button.setAttribute('aria-label', isActive ? removeLabel : addLabel);
                    icon.classList.toggle('fa-solid', isActive);
                    icon.classList.toggle('fa-regular', !isActive);

                    if (isActive) {
                        if (!methodInput) {
                            methodInput = document.createElement('input');
                            methodInput.type = 'hidden';
                            methodInput.name = '_method';
                            form.appendChild(methodInput);
                        }

                        methodInput.value = 'DELETE';
                    } else if (methodInput) {
                        methodInput.remove();
                    }
                }

                document.addEventListener('submit', async function (event) {
                    const form = event.target.closest('[data-wishlist-toggle]');

                    if (!form) {
                        return;
                    }

                    event.preventDefault();
                    event.stopPropagation();

                    const button = form.querySelector('[data-wishlist-button]');

                    if (button.disabled) {
                        return;
                    }

                    const methodInput = form.querySelector('input[name="_method"]');
                    const isRemoving = methodInput && methodInput.value.toUpperCase() === 'DELETE';

                    button.disabled = true;
                    button.setAttribute('aria-busy', 'true');

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            body: new FormData(form),
                            credentials: 'same-origin',
                            redirect: 'follow',
                            headers: {
                                'Accept': 'text/html, application/xhtml+xml',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (!response.ok) {
                            throw new Error('Wishlist request failed.');
                        }

                        setWishlistState(form, !isRemoving);
                    } catch (error) {
                        HTMLFormElement.prototype.submit.call(form);
                    } finally {
                        button.disabled = false;
                        button.removeAttribute('aria-busy');
                    }
                });
            })();
        </script>
    @endpush
@endonce
