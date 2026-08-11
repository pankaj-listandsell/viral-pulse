@php use App\Enums\PostStatus; @endphp

<div class="relative inline-block text-left" x-data="{ open: false }" @keydown.escape.window="open = false">
    <button
        type="button"
        @click="open = !open"
        class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600
               dark:hover:bg-gray-800 dark:hover:text-gray-300"
        :aria-expanded="open"
        aria-haspopup="menu"
        aria-label="Actions for {{ $post->title }}"
    >
        <x-icon name="chevron-down" class="size-4" />
    </button>

    <div
        x-show="open"
        x-transition.origin.top.right
        @click.outside="open = false"
        class="absolute right-0 z-20 mt-1 w-48 overflow-hidden rounded-xl border border-gray-200 bg-white py-1 text-left shadow-lg
               dark:border-gray-800 dark:bg-gray-900"
        role="menu"
        x-cloak
    >
        @if($post->trashed())
            <form method="POST" action="{{ route('admin.posts.restore', $post->id) }}">
                @csrf
                <button type="submit" class="flex w-full items-center gap-2.5 px-3 py-2 text-sm transition hover:bg-gray-50 dark:hover:bg-gray-800">
                    <x-icon name="arrow-left" class="size-4" />
                    Restore
                </button>
            </form>

            <x-ui.confirm-form
                :action="route('admin.posts.force-delete', $post->id)"
                title="Delete permanently?"
                message="This post and its comments will be gone for good."
                confirm-label="Delete forever"
            >
                <x-slot:trigger>
                    <button type="submit" class="flex w-full items-center gap-2.5 px-3 py-2 text-sm text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10">
                        <x-icon name="trash-2" class="size-4" />
                        Delete forever
                    </button>
                </x-slot:trigger>
            </x-ui.confirm-form>
        @else
            <a href="{{ route('admin.posts.edit', $post) }}"
               class="flex items-center gap-2.5 px-3 py-2 text-sm transition hover:bg-gray-50 dark:hover:bg-gray-800"
               role="menuitem">
                <x-icon name="pencil" class="size-4" />
                Edit
            </a>

            @if($post->status === PostStatus::Published)
                <form method="POST" action="{{ route('admin.posts.unpublish', $post) }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2.5 px-3 py-2 text-sm transition hover:bg-gray-50 dark:hover:bg-gray-800">
                        <x-icon name="eye" class="size-4" />
                        Unpublish
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.posts.publish', $post) }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2.5 px-3 py-2 text-sm text-green-700 transition hover:bg-green-50 dark:text-green-400 dark:hover:bg-green-500/10">
                        <x-icon name="check" class="size-4" />
                        Publish now
                    </button>
                </form>
            @endif

            <form method="POST" action="{{ route('admin.posts.duplicate', $post) }}">
                @csrf
                <button type="submit" class="flex w-full items-center gap-2.5 px-3 py-2 text-sm transition hover:bg-gray-50 dark:hover:bg-gray-800">
                    <x-icon name="file-plus" class="size-4" />
                    Duplicate
                </button>
            </form>

            @if($post->status !== PostStatus::Archived)
                <form method="POST" action="{{ route('admin.posts.archive', $post) }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2.5 px-3 py-2 text-sm transition hover:bg-gray-50 dark:hover:bg-gray-800">
                        <x-icon name="inbox" class="size-4" />
                        Archive
                    </button>
                </form>
            @endif

            <div class="my-1 border-t border-gray-100 dark:border-gray-800"></div>

            <x-ui.confirm-form
                :action="route('admin.posts.destroy', $post)"
                title="Move to trash?"
                message="You can restore it from the trash later."
                confirm-label="Move to trash"
            >
                <x-slot:trigger>
                    <button type="submit" class="flex w-full items-center gap-2.5 px-3 py-2 text-sm text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10">
                        <x-icon name="trash-2" class="size-4" />
                        Move to trash
                    </button>
                </x-slot:trigger>
            </x-ui.confirm-form>
        @endif
    </div>
</div>
