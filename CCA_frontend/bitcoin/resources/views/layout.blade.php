<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="Bc. Tomáš Drozda">
    <title>Cryptocurrency blockchain analysis</title>
    <link rel="stylesheet" href="/css/app.css"/>
    <script type="text/javascript" src="http://code.jquery.com/jquery-1.12.0.min.js"></script>
    <script type="text/javascript" src="/js/bootstrap.min.js"></script>

    @stack('styles')
</head>
<body>
<nav class="navbar navbar-inverse navbar-fixed-top">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="{{route('block_findall', ['currency' => 'bitcoin'])}}">Cryptocurrency blockchain analysis</a>
            <ul class="nav navbar-nav navbar-right">
                <li><a href="{{route('block_findall', ['currency' => 'bitcoin'])}}">Bitcoin blocks</a></li>
                <li><a href="{{route('block_findall', ['currency' => 'litecoin'])}}">Litecoin blocks</a></li>
            </ul>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
            <form class="navbar-form navbar-right" action="{{route('search', ['currency'=>'bitcoin'])}}" method="post">
                <input style="width:570px;" type="text" name="search" class="form-control" placeholder="Block height, Block, Transaction, Address">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
            </form>
        </div>
    </div>
</nav>
<div class="container" style="margin-top: 55px;">
    <div class="clearfix"></div>
    @if(\Session::has('message'))
        <div class="alert alert-dismissible {{\Underscore\Types\Arrays::get(['info' => 'alert-info'], \Session::get('message.type'))}}" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            {!!\Session::get('message.text')!!}
        </div>
    @endif
    @yield('content')
    <div class="clearfix"></div>
    <div class="row">
        <div class="col-md-offset-12">
            {{--<a class="back-to-top glyphicon glyphicon-arrow-up" href="#" title="Top">Top</a>--}}
            <a href="#0" class="cd-top">Top</a>
        </div>
    </div>
    <footer>
        Bitcoin Blockchain analysis
    </footer>
</div>
</body>
<script type="text/javascript" href="/js/modernizr.js"></script>
<script type="text/javascript" href="/js/bootstrap.min.js"></script>
@stack('scripts')

<script type="text/javascript">
    jQuery(document).ready(function($){
        // browser window scroll (in pixels) after which the "back to top" link is shown
        var offset = 300,
        //browser window scroll (in pixels) after which the "back to top" link opacity is reduced
                offset_opacity = 1200,
        //duration of the top scrolling animation (in ms)
                scroll_top_duration = 700,
        //grab the "back to top" link
                $back_to_top = $('.cd-top');

        //hide or show the "back to top" link
        $(window).scroll(function(){
            ( $(this).scrollTop() > offset ) ? $back_to_top.addClass('cd-is-visible') : $back_to_top.removeClass('cd-is-visible cd-fade-out');
            if( $(this).scrollTop() > offset_opacity ) {
                $back_to_top.addClass('cd-fade-out');
            }
        });

        //smooth scroll to top
        $back_to_top.on('click', function(event){
            event.preventDefault();
            $('body,html').animate({
                        scrollTop: 0 ,
                    }, scroll_top_duration
            );
        });


        $('.collapse').on('shown.bs.collapse', function (evt) {
            var $this = $(this);
            var txid  = $this.attr('data-txid');
            console.log($this.html.length );
            if($this.html.length == 1){
                $this.html($.get('/{{$currency}}/transaction/'+txid+'/structure',function(data){
                    $this.html(data);
                }));
            }
        });

    });
</script>
</html>
