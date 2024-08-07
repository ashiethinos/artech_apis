<!doctype html>
<html lang="en">

<head>
    <title>Job Details-Artech</title>
    <?php include_once('includes/header.php') ?>
</head>

<body class="artech-job">

    <!-- =====Header Part==== -->
    <section class="job-detail-header py-5 mb-5">
        <div class="container">
            <div class="py-5 text-center">
                <h1 class="job-title text-white mb-2">Selenium Tester</h1>
                <p class="job-location text-white mb-2">Bengaluru, Karnataka</p>
                <p class="text-white mb-2 fs-12">All On-site</p>
            </div>
        </div>
    </section>
    <!-- ========================= -->
    <!-- =======Job List Content======= -->
    <div class="container">
        <section class="m-3">
            <div class="row">
                <div class="col-md-12">
                    <div class="card mt--100">
                        <div class="card-body">
                            <div id="job-details">

                            </div>
                            <div class="card mt-4 p-2">
                                <div class="card-body py-4">
                                    <div class="col-md-12 mb-4">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h3 class="fs-20 fw-500">Application form</h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <form action="">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <input type="text" class="form-control fs-16"
                                                        placeholder="First Name">
                                                </div>
                                                <div class="col-md-6  mb-3">
                                                    <input type="text" class="form-control fs-16"
                                                        placeholder="Last Name">
                                                </div>
                                                <div class="col-md-6  mb-3">
                                                    <input type="email" class="form-control fs-16"
                                                        placeholder="Email ID">
                                                </div>
                                                <div class="col-md-6  mb-3">
                                                    <input type="number" class="form-control fs-16"
                                                        placeholder="Phone No">
                                                </div>
                                                <div class="col-md-12  mb-3">
                                                    <input type="text" class="form-control fs-16"
                                                        placeholder="Additional Info">
                                                </div>
                                                <div class="col-md-12">
                                                    <fieldset class="upload_dropZone text-center mb-3 p-4">
                                                        <legend class="visually-hidden">Image uploader</legend>
                                                        <img src="images/upload.svg" alt="">
                                                        <p class="small my-2 text-grey">Drag and drop a file<br></p>
                                                        <input id="upload_image_logo" data-post-name="image_logo"
                                                            data-post-url="https://someplace.com/image/uploads/logos/"
                                                            class="position-absolute invisible" type="file" multiple
                                                            accept="image/jpeg, image/png, image/svg+xml" />
                                                        <label class="" for="upload_image_logo"><i
                                                                class="text-grey">or</i> <span class="text-voilet">
                                                                browse your device</span> </label>
                                                        <div
                                                            class="upload_gallery d-flex flex-wrap justify-content-center gap-3 mb-0">
                                                        </div>
                                                    </fieldset>
                                                </div>
                                                <div class="col-md-12 text-center">
                                                    <button class="btn btn-jl-common">Submit</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <!-- ========================= -->
    <?php include_once('includes/footer.php') ?>
</body>

</html>
<script>
$(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const jobId = urlParams.get('job_id');
    $.ajax({
        url: 'oracle_single_job_api.php',
        type: 'GET',
        data: {
            jobId: '656'
        },
        success: function(response) {
            const res = JSON.parse(response);
            const job = res.job;
            console.log(job)
            renderJobDetails(job)


        }
    });


    function renderJobDetails(job) {
        const jobData = `
                     <div class="row">
                                    <div class="col-md-6">
                                        <p class="fw-700 fs-18">${job.title}</p>
                                        <p class="mb-0 fs-12 text-grey"><svg xmlns="http://www.w3.org/2000/svg"
                                                width="16" height="16" fill="currentColor" class="bi bi-geo-alt"
                                                viewBox="0 0 16 16">
                                                <path
                                                    d="M12.166 8.94c-.524 1.062-1.234 2.12-1.96 3.07A32 32 0 0 1 8 14.58a32 32 0 0 1-2.206-2.57c-.726-.95-1.436-2.008-1.96-3.07C3.304 7.867 3 6.862 3 6a5 5 0 0 1 10 0c0 .862-.305 1.867-.834 2.94M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10" />
                                                <path
                                                    d="M8 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4m0 1a3 3 0 1 0 0-6 3 3 0 0 0 0 6" />
                                            </svg>&nbsp; ${job.primaryLocation}</p>
                                    </div>
                                    <div class="col-md-6 m-auto text-end">
                                        <button class="btn btn-jl-common">Apply Now</button>
                                        <a href="#"><img src="images/share.svg" class="w-10" alt=""></a>
                                    </div>
                                </div>
                                <hr>
                                <div class="col-md-12 p-2">
                                    <p class="fs-18 fw-500 mb-2">Job Description</p>
                                    <p class="job-title text-grey mb-2">${job.title}</p>
                                    <p><span class="fw-500 mb-2">Duration :</span><span class="text-grey"> 3
                                            months</span>
                                    </p>
                                    <p class="text-grey">This position is based at our Bengaluru office</p>
                                </div>
                                  ${job.externalDescriptionStr ? `  <div class="col-md-12 p-2 mt-4">
                                    <h3 class="fs-20 fw-500">Summary</h3>
                                    <p class="mb-0 text-grey">${job.externalDescriptionStr}</p>
                                </div>` : ``}
                              ${job.corporateDescriptionStr ? `
                                  <div class="col-md-12 p-2 mt-4">
                                    <h3 class="fs-20 fw-500">Roles & Responsibilities</h3>
                                    <p class="mb-0 text-grey"> ${job.corporateDescriptionStr}</p>
                                </div>` : ""}
                             
                            
                                <div class="col-md-12 p-2 mt-4">
                                    <p class="mb-0">You may reach me directly at <a class="link"
                                            href="mailto:bhagyashree.Kulkarni@artechinfo.in">Bhagyashree.Kulkarni@artechinfo.in</a>
                                    </p>
                    </div>`
        $('#job-details').append(jobData)

        $('.job-title').text(job.title)
        $('.job-location').text(job.primaryLocation)

    }
});
</script>