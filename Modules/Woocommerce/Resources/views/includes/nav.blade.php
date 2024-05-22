<nav class="mt-3 navbar navbar-expand-lg navbar-light bg-light">
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav">
            <li class="nav-item {{ (substr(url()->current(), strrpos(url()->current(), '/' )+1) == 'woocommerce') ? 'active' : '' }}">
                <a class="nav-link" href="{{route('woocommerce.index')}}">WooCommerce</a>
            </li>
            <li class="nav-item {{ (substr(url()->current(), strrpos(url()->current(), '/' )+1) == 'sync-log') ? 'active' : '' }}">
                <a class="nav-link" href="{{route('woocommerce.sync-log')}}">{{trans('file.Sync Log')}}</a>
            </li>
            <li class="nav-item {{ (substr(url()->current(), strrpos(url()->current(), '/' )+1) == 'settings') ? 'active' : '' }}">
                <a class="nav-link" href="{{route('woocommerce.settings')}}">{{trans('file.settings')}}</a>
            </li>
        </ul>
    </div>
</nav>
