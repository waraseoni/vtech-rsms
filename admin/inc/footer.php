<script>
  // Put uni_modal and viewer_modal in global scope (outside document.ready)
  window.viewer_modal = function($src = ''){
    start_loader()
    var t = $src.split('.')
    t = t[1]
    if(t =='mp4'){
      var view = $("<video src='"+$src+"' controls autoplay></video>")
    }else{
      var view = $("<img src='"+$src+"' />")
    }
    $('#viewer_modal .modal-content video,#viewer_modal .modal-content img').remove()
    $('#viewer_modal .modal-content').append(view)
    $('#viewer_modal').modal({
      show:true,
      backdrop:'static',
      keyboard:false,
      focus:true
    })
    end_loader()  
  }

  window.uni_modal = function($title = '' , $url='',$size=""){
    start_loader()
    $.ajax({
      url:$url,
      error:err=>{
        console.error(err)
        alert("An error occured while loading content")
        end_loader()
      },
      success:function(resp){
        if(resp){
          $('#uni_modal .modal-title').html($title)
          $('#uni_modal .modal-body').html(resp)
          if($size != ''){
            $('#uni_modal .modal-dialog').addClass($size+'  modal-dialog-centered')
          }else{
            $('#uni_modal .modal-dialog').removeAttr("class").addClass("modal-dialog modal-md modal-dialog-centered")
          }
          $('#uni_modal').modal({
            show:true,
            backdrop:'static',
            keyboard:false,
            focus:true
          })
        } else {
            alert("Empty response received from server")
        }
        end_loader()
      }
    })
  }

  window._conf = function($msg='',$func='',$params = []){
    var paramsStr = $params.map(function(p) {
      if (typeof p === 'string') {
        return "'" + p.replace(/'/g, "\\'") + "'";
      }
      return p;
    }).join(',');
    $('#confirm_modal #confirm').attr('onclick',$func+"("+paramsStr+")")
    $('#confirm_modal .modal-body').html($msg)
    $('#confirm_modal').modal('show')
  }

  $(document).ready(function(){
    // Initialize any document-ready functions here if needed
  })
</script>

<footer class="main-footer text-sm">
        <strong>Copyright © <?php echo date('Y') ?>. 
        </strong>
        All rights reserved.
        <div class="float-right d-none d-sm-inline-block">
          <b><?php echo $_settings->info('short_name') ?> (by: <a href="mailto:vtech.jbp@gmail.com" target="blank">vtech.jbp</a> )</b> v1.0
        </div>
      </footer>
    </div>
    <!-- ./wrapper -->
    
    <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
    <script src="<?php echo base_url ?>assets/js/mobile-sidebar.js"></script>
    <script src="<?php echo base_url ?>plugins/jquery-ui/jquery-ui.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="<?php echo base_url ?>plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- ChartJS -->
    <script src="<?php echo base_url ?>plugins/chart.js/Chart.min.js"></script>
    <!-- Sparkline -->
    <script src="<?php echo base_url ?>plugins/sparklines/sparkline.js"></script>
    <!-- Select2 -->
    <script src="<?php echo base_url ?>plugins/select2/js/select2.full.min.js"></script>
    <!-- JQVMap -->
    <script src="<?php echo base_url ?>plugins/jqvmap/jquery.vmap.min.js"></script>
    <script src="<?php echo base_url ?>plugins/jqvmap/maps/jquery.vmap.usa.js"></script>
    <!-- jQuery Knob Chart -->
    <script src="<?php echo base_url ?>plugins/jquery-knob/jquery.knob.min.js"></script>
    <!-- daterangepicker -->
    <script src="<?php echo base_url ?>plugins/moment/moment.min.js"></script>
    <script src="<?php echo base_url ?>plugins/daterangepicker/daterangepicker.js"></script>
    <!-- Tempusdominus Bootstrap 4 -->
    <script src="<?php echo base_url ?>plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
    <!-- Summernote -->
    <script src="<?php echo base_url ?>plugins/summernote/summernote-bs4.min.js"></script>
    <script src="<?php echo base_url ?>plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="<?php echo base_url ?>plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="<?php echo base_url ?>plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
    <script src="<?php echo base_url ?>plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
    <!-- overlayScrollbars -->
     <script src="<?php echo base_url ?>plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
    <!-- AdminLTE App -->
    <script src="<?php echo base_url ?>dist/js/adminlte.js"></script>
    <!-- Universal Export Helper -->
    <script src="<?php echo base_url ?>dist/js/export_helper.js"></script>
