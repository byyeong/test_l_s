<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>비밀번호 변경 · Travelloda</title>
  <link rel="apple-touch-icon" sizes="180x180" href="/images/favicon/apple-touch-icon.png">
  <link rel="icon" type="image/png" href="/images/favicon/favicon-32x32.png" sizes="32x32">
  <link rel="icon" type="image/png" href="/images/favicon/favicon-16x16.png" sizes="16x16">
  <meta name="description" content="Travelloda">
  <meta name="author" content="6009.co.kr">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
  <link rel="stylesheet" href="/css/style.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
</head>

<body>
<div class="wrap">

  <div class="container">
    <div class="memberForm">
      <h1 class="logo"><img src="/images/logo_travelloda.svg" alt="Travelloda"></h1>
      @if ($res == 1)
          <form class="form-horizontal writingForm" method="POST" id="helpForm">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" name="token" value="{{ $data['token'] }}">
            <input type="hidden" name="email" value="{{ $data['email'] }}">
            <div class="form-group plainTextWrap">
            <label for="exampleInputEmail1" class="sr-only">이메일 주소</label>
            <input type="email" readonly class="form-control-plaintext" id="email" placeholder="이메일을 입력하세요." value="{{ $data['email'] }}">
            </div>
            <div class="form-group">
            <label for="password1" class="sr-only">변경할 비밀번호</label>
            <input type="password" class="form-control" id="password1" name="password" placeholder="새 비밀번호 (8~32 문자, 숫자, 특수문자)" max="32" min="8" required>
            <!-- <small class="form-text text-muted"></small> -->
            </div>
            <div class="form-group formLast">
            <label for="password2" class="sr-only">변경할 비밀번호 확인</label>
            <input type="password" class="form-control" id="password2" name="password_confirmation" placeholder="비밀번호 확인" max="32" min="8" required>
            <div class="invalid-feedback" id="invalid-feedback">비밀번호가 일치하지 않습니다.</div>
            <div class="invalid-feedback" id="invalid-size-feedback">비밀번호는 8-32자 입니다.</div>
            </div>
            <button type="submit" disabled class="btn btn-block">비밀번호 변경하기</button>
        </form>
        <div class="complete" style="display: none;">
            <h2 class="memberMidTitle nsr"><span>비밀번호가</span> <span>변경되었습니다.</span></h2>
            <span class="form-text text-loda-acc">새 비밀번호로 로그인 해주세요.</span>
            <br>
            <a href="{{ config('app.app_link') }}" class="btn btn-block btn-loda-acc nsr">트레블로다로 이동</a>
        </div><!-- /.complete -->
      @else
        <div class="complete">
            <h2 class="memberMidTitle nsr"><span>죄송합니다.</span><span> 이 비밀번호 변경 링크는 유효하지 않습니다. </span></h2>
            <span class="form-text text-loda-acc">다시 비밀번호 재설정 링크를 요청해 주세요.</span>
            <br>
            <a href="{{ config('app.app_link') }}" class="btn btn-block btn-loda-acc nsr">트레블로다로 이동</a>
        </div><!-- /.complete -->
      @endif
      
    </div><!-- /.memberForm -->
  </div><!-- /.container -->

</div><!-- /.wrap -->

<script>
  $(document).ready(function(){
    validate();
    $('#password1, #password2').keyup(validate);

    $("form#helpForm").submit(function(e) {   
        e.preventDefault();            
        if ($('input[name=password]').val().length < 8 || $('input[name=password]').val().length > 32) {
            $('#invalid-size-feedback').show();
            return false;
        }
        if ($('input[name=password]').val() != $('input[name=password_confirmation]').val()) {
            $('#invalid-feedback').show();
            return false;
        }
        $('.invalid-feedback').hide();
        var formData = new FormData(this);
        jQuery.ajax({
            url: '/api/password/reset',
            type: 'POST',
            data:formData,
            cache: false,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function (result) {
                $('.writingForm').remove();
                $('.complete').show();
            }
        });
    });
  });
  function validate(){
    if ($('#password1').val().length > 7 &&
        $('#password2').val().length > 7) {
        $("button[type=submit]").prop("disabled", false).addClass('btn-loda-ready');
    }
    else {
        $("button[type=submit]").prop("disabled", true).removeClass('btn-loda-ready');
    }
  }
</script>

</body>
</html>
