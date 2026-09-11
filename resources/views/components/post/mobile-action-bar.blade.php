@props(['post', 'shareProps' => [], 'likeProps' => []])

@php
    $url = route('posts.show', $post);
    $encodedUrl = urlencode($url);
    $encodedTitle = urlencode($post->title);
    $whatsappText = urlencode("🔥 *{$post->title}*\n\nRead more here: {$url}");
@endphp

<div class="fixed inset-x-0 bottom-0 z-40 block border-t border-gray-200/80 bg-white/95 px-3 py-2 shadow-2xl backdrop-blur-md transition-transform duration-300 lg:hidden dark:border-gray-800/80 dark:bg-gray-950/95"
     id="mobile-action-bar">
    <div class="mx-auto flex max-w-lg items-center justify-between gap-2">
        {{-- WhatsApp Quick Share (High conversion) --}}
        <a href="https://api.whatsapp.com/send?text={{ $whatsappText }}"
           target="_blank" rel="noopener noreferrer"
           class="flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-3 py-2 text-xs font-black text-white shadow-xs transition active:scale-95 hover:bg-emerald-700">
            <svg class="size-4 fill-current" viewBox="0 0 24 24">
                <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.099.824zm-3.392-12.416c-5.514 0-10 4.486-10 10 0 1.923.545 3.719 1.488 5.249l-1.527 5.583 5.736-1.505c1.474.873 3.195 1.373 5.303 1.373 5.514 0 10-4.486 10-10s-4.486-10-10-10z"/>
            </svg>
            <span>WhatsApp</span>
        </a>

        {{-- Telegram Share --}}
        <a href="https://t.me/share/url?url={{ $encodedUrl }}&text={{ $encodedTitle }}"
           target="_blank" rel="noopener noreferrer"
           class="flex items-center justify-center rounded-xl border border-gray-200 bg-gray-50 px-2.5 py-2 text-xs font-bold text-sky-600 transition active:scale-95 hover:bg-sky-50 dark:border-gray-800 dark:bg-gray-900 dark:text-sky-400">
            <svg class="size-4 fill-current" viewBox="0 0 24 24">
                <path d="m20.665 3.717-17.73 6.837c-1.21.486-1.203 1.161-.222 1.462l4.552 1.42 10.532-6.645c.498-.303.953-.14.579.192l-8.533 7.701h-.002l-.313 4.673c.46 0 .663-.211.921-.46l2.211-2.15 4.599 3.397c.848.467 1.457.227 1.668-.785l3.019-14.228c.309-1.239-.473-1.8-1.282-1.414z"/>
            </svg>
        </a>

        {{-- Copy Link Button --}}
        <button type="button"
                id="mobile-copy-link-btn"
                data-url="{{ $url }}"
                class="flex items-center justify-center gap-1 rounded-xl border border-gray-200 bg-gray-50 px-2.5 py-2 text-xs font-bold text-gray-700 transition active:scale-95 hover:bg-gray-100 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="14" height="14" x="8" y="8" rx="2" ry="2"/>
                <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>
            </svg>
            <span id="mobile-copy-text" class="sr-only">Copy</span>
        </button>

        {{-- Bookmark & Like Island Mounts --}}
        <div class="flex items-center gap-1.5 border-l border-gray-200/80 pl-1.5 dark:border-gray-800/80">
            <span data-island="BookmarkButton" data-island-eager
                  data-props="{{ json_encode(['post' => ['id' => $post->id, 'title' => $post->title, 'slug' => $post->slug, 'category' => $post->category?->name, 'url' => $url]]) }}"></span>

            @if(app(\App\Services\SettingsService::class)->bool('likes_enabled', true))
                <span data-island="LikeButton" data-props="{{ json_encode($likeProps) }}"></span>
            @endif
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const copyBtn = document.getElementById('mobile-copy-link-btn');
        if (copyBtn) {
            copyBtn.addEventListener('click', async () => {
                const url = copyBtn.getAttribute('data-url');
                try {
                    if (navigator.clipboard) {
                        await navigator.clipboard.writeText(url);
                    } else {
                        const temp = document.createElement('input');
                        temp.value = url;
                        document.body.appendChild(temp);
                        temp.select();
                        document.execCommand('copy');
                        document.body.removeChild(temp);
                    }
                    copyBtn.classList.add('bg-emerald-100', 'text-emerald-700', 'dark:bg-emerald-950', 'dark:text-emerald-300');
                    setTimeout(() => {
                        copyBtn.classList.remove('bg-emerald-100', 'text-emerald-700', 'dark:bg-emerald-950', 'dark:text-emerald-300');
                    }, 2000);
                } catch (e) {
                    console.error('Failed to copy link', e);
                }
            });
        }
    });
</script>
