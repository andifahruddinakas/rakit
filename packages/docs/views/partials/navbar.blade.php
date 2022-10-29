<!-- Navbar statrt -->
<nav id="navbar" class="navbar is-fixed-top has-shadow" role="navigation" aria-label="main navigation">
    <div class="container">
        <div class="navbar-brand">
            <div class="navbar-item">
                <b id="docs-title">{{ __('docs::docs.navbar.documentation') }}</b> &nbsp; <span class="tag is-normal is-rounded is-link is-light">{{ RAKIT_VERSION }}</span>
            </div>
            <div id="navbarBurger" class="navbar-burger burger" data-target="navMenuMore">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
        <div id="navMenuMore" class="navbar-menu">
            <div class="navbar-start">
                <div class="navbar-item">
                    <div class="dropdown" id="docsearch">
                        <div class="dropdown-trigger">
                            <div class="field control">
                                <input id="userinput" class="input is-rounded is-narrow" type="search" placeholder="Cari..."/>
                            </div>
                        </div>
                        <div class="dropdown-menu" id="search-results" role="menu">
                            <div class="dropdown-content" id="suggestions"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="navbar-end">
                <a class="navbar-item" id="homepage" href="{{ URL::home() }}">{{ __('docs::docs.navbar.home') }}</a>
                <a class="navbar-item" id="docs" href="{{ URL::to('docs/'.config('application.language')) }}">{{ __('docs::docs.navbar.documentation') }}</a>
                <a class="navbar-item" id="api" href="https://rakit.esyede.my.id/api/v{{ RAKIT_VERSION }}/index.html" target="_blank">{{ __('docs::docs.navbar.api') }}</a>
                <a class="navbar-item" id="repos" href="https://rakit.esyede.my.id/repositories" target="_blank">{{ __('docs::docs.navbar.repositories') }}</a>
                <a class="navbar-item" id="forum" href="https://rakit.esyede.my.id/forum" target="_blank">{{ __('docs::docs.navbar.forum') }}</a>
                <a class="navbar-item" id="github" href="https://github.com/esyede/rakit" target="_blank">{{ __('docs::docs.navbar.vcs') }}</a>
                <div class="navbar-item">
                    <div class="buttons">
                        <span class="button is-rounded is-small is-info" id="docs-lang" data-value="{{ config('application.language') }}">{{ ('id' === config('application.language')) ? 'Indonesian' : 'English' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>
<div style="margin-bottom: 50px"></div>
