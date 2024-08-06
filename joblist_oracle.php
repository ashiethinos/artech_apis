<!doctype html>
<html lang="en">

<head>
    <title>Job list-Artech</title>
    <?php include_once('includes/header.php');

include './config.php';
?>
</head>

<body class="artech-job">

    <!-- =====Header Part==== -->
    <section class="jobheader py-5 mb-5">
        <div class="container">
            <div class="text-white">HOME > CAREER > CORPORATE</div>
            <div class="py-5">
                <h1 class="text-white mb-4">Consulting Jobs in India: Let Artech Empower Your Career</h1>
                <p class="text-white">As a Test Automation Engineer, you will be responsible for transforming testing
                    into a continuous and efficient end-to-end quality engineering function through the use of quality
                    processes, tools, and methodologies Read More</p>
            </div>
        </div>
    </section>
    <!-- ========================= -->
    <!-- =======Job List Content======= -->
    <div class="container">
        <section class="mb-3 d-none d-lg-block">
            <div class="row">
                <div class="col-md-5">
                    <a href="#">Create Job Alert</a>
                </div>
                <div class="col-md-7">
                    <div class="row">
                        <div class="col-md-3 text-end">
                            <a href="#">Search Jobs</a>
                        </div>
                        <div class="col-md-3 text-center">
                            <a href="#">Sign In</a>
                        </div>
                        <div class="col-md-3 text-end">
                            <a href="#">Register With Us</a>
                        </div>
                        <div class="col-md-3 text-end">
                            <a href="#">Language</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- =====For Mobile & Tab==== -->
        <section class="mb-3 d-sm-block d-md-block d-lg-none">
            <div class="col-md-12 text-end">
                <button class="btn btn-jl-common" onclick="jltoggle()"><svg xmlns="http://www.w3.org/2000/svg"
                        width="16" height="16" fill="currentColor" class="bi bi-list" viewBox="0 0 16 16">
                        <path fill-rule="evenodd"
                            d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5" />
                    </svg></button>
            </div>
            <div class="row" id="contentBox" style="display: none;">
                <div class="col-md-12 mb-3 mt-3 bdr-1">
                    <ul class="contentBox-content">
                        <li><a href="#">Create Job Alert</a></li>
                        <li><a href="#">Search Jobs</a></li>
                        <li><a href="#">Sign In</a></li>
                        <li><a href="#">Register With Us</a></li>
                        <li><a href="#">Language</a></li>
                    </ul>
                </div>
            </div>
        </section>
        <!-- ========================= -->
        <section class="mb-5">
            <div class="row ">
                <div class="col-md-12 mb-3 bdr-1">
                    <form action="" id="job-search-form">
                        <div class="row">
                            <div class="col-md-4 bdr-right">
                                <div class="input-group">
                                    <span class="input-group-text" id="basic-addon1"><img src="images/search.svg"
                                            alt=""></span>
                                    <input id="search-keyword" type="text" class="form-control jl-search"
                                        placeholder="Job title, keywords or company"
                                        aria-label="Job title, keywords or company" aria-describedby="basic-addon1">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="input-group">
                                    <span class="input-group-text" id="basic-addon1"><img src="images/location.svg"
                                            alt=""></span>
                                    <select multiple name="" id="" class="locations form-control form-select jl-search">
                                       
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-5 m-auto text-end">
                                <button class="btn btn-jl-common">Find Jobs</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="col-md-2 bdr-2 mr-20">
                    <form action="">
                        <select multiple name="" data-group="category" id="categories" class="form-control form-select jl-search">

                        </select>
                    </form>
                </div>
                <div class="col-md-2 bdr-2 mr-20">
                    <form action="">
                        <select multiple name=""  data-group="location" class="locations form-control form-select jl-search">
                            
                        </select>
                    </form>
                </div>
                <div class="col-md-2 bdr-2 mr-20">
                    <form action="">
                        <select  multiple name="" id="degreeLevels" data-group="degree" class="form-control form-select jl-search">
                            <option value="">Industry</option>
                        </select>
                    </form>
                </div>
                <div class="col-md-2  text-right">
                <button style="font-size:13px" class="btn btn-jl-common reset-filters">Clear Filters</button>
                <span class="jobs-count"></span>
                </div>
            </div>
        </section>
        <section class="mb-5">
            <div class="row d-flex align-items-start">
                <div class="col-md-12 text-center">
                    <span class="loader"></span>
                </div>
                <div class="col-md-5 list-scroll">
                    <div id="jobs-container" class="nav flex-column nav-pills" id="v-pills-tab" role="tablist"
                        aria-orientation="vertical">


                    </div>
                </div>
                <div class="col-md-7">
                    <div class="tab-content" id="v-pills-tabContent">

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
    $(".loader").addClass("active");

    function fetchData(apiUrl, params = []) {
        return $.ajax({
            url: apiUrl,
            type: "GET",
            data: params
        });
    }

    function renderJobElements(jobs) {

        const container = $("#jobs-container");
        container.empty();

        const showJobContainer = $(".tab-content");
        showJobContainer.empty();

        jobs.forEach((job, index) => {
            const jobElement = createJobElement(job, index);
            const jobDetailElement = createJobDetailElement(job, index);
            container.append(jobElement);
            showJobContainer.append(jobDetailElement);
        });

        $('.jobs-count').text(`${jobs.length} Jobs Found`)
    }
    

    function createJobElement(job, index) {
        return `
            <div class="${index === 0 ? "active" : ""} job-container mb-3" id="v-pills-home-tab" data-bs-toggle="pill" data-bs-target="#v-pills-jobcard${job.id}" type="button" role="tab" aria-controls="v-pills-home" aria-selected="true">
                <div class="col-md-12 bdr-1 py-4 px-2">
                    <div class="row">
                        <div class="col-md-6 col-6"><button class="btn ${job.jobType === "On-site" ? "btn-on-site" : "btn-remote"}">${job.jobType || "On-site"}</button></div>
                        <div class="col-md-6 col-6 m-auto text-end">${job.postedDate || "Date"}</div>
                        <div class="col-md-12 text-center fw-700 my-3">${job.title || "Title"}</div>
                        ${job.skills && job.skills.length > 0 ? `
                            <div class="col-md-12">
                                <div class="row justify-content-center">
                                    ${job.skills.map(skill => `<div class="col-lg-2 col-5 bdr-2 text-center m-1 fs-12">${skill}</div>`).join("")}
                                </div>
                            </div>` : ``}
                        ${job.externalPostedEndDate ? `<div class="col-md-12 text-center"><p class="mb-0 mt-4 fs-12">Apply By : ${job.externalPostedEndDate}</p></div>` : ``}
                        ${job.externalDescriptionStr ? `
                            <div class="col-md-12">
                                <p class="mb-0 mt-1 fs-12">
                                    ${job.externalDescriptionStr.length > 100 ? `${job.externalDescriptionStr.substring(0, 100)}...` : job.externalDescriptionStr}
                                </p>
                            </div>` : ""}
                    </div>
                </div>
            </div>
        `;
    }

    function createJobDetailElement(job, index) {
        return `
            <div class="${index === 0 ? "active show" : ""} tab-pane fade" id="v-pills-jobcard${job.id}" role="tabpanel" aria-labelledby="v-pills-profile-tab" tabindex="0">
                <div class="row">
                    <div class="col-md-12 bdr-1 py-4 px-2 mb-3">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="fw-700 fs-18">${job.title}</p>
                                <p class="mb-0 fs-12 text-grey">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-geo-alt" viewBox="0 0 16 16">
                                        <path d="M12.166 8.94c-.524 1.062-1.234 2.12-1.96 3.07A32 32 0 0 1 8 14.58a32 32 0 0 1-2.206-2.57c-.726-.95-1.436-2.008-1.96-3.07C3.304 7.867 3 6.862 3 6a5 5 0 0 1 10 0c0 .862-.305 1.867-.834 2.94M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10"/><path d="M8 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4m0 1a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/>
                                    </svg>&nbsp; ${job.primaryLocation}
                                </p>
                            </div>
                            <div class="col-md-6 m-auto text-end">
                                <button class="btn btn-jl-common">Apply Now</button>
                                <a href="#"><img src="images/share.svg" class="w-10" alt=""></a>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <p class="fs-18 fw-500 mb-2">Job Description</p>
                                <p class="text-grey mb-2">${job.title || "No Job Description [no such field]"}</p>
                                ${job.duration ? `<p><span class="fw-500 mb-2">Duration :</span><span class="text-grey"> ${job.duration}</span></p>` : ``}
                                ${job.externalDescriptionStr ? `<p class="text-grey">${job.externalDescriptionStr}</p>` : ``}
                            </div>
                            ${job.corporateDescriptionStr ? `
                                <div class="col-md-12 mt-4">
                                    <p class="fs-18 fw-500 mb-2">About Us</p>
                                    <p class="mb-0 text-grey">${job.corporateDescriptionStr || "No Job Full Description [no such field]"}</p>
                                </div>` : ""}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderDropdownOptions(elementId, options) {
        const dropdown = $(elementId);
        dropdown.empty();
        options.forEach(option => {
            dropdown.append(`<option value="${option}">${option}</option>`);
        });
    }

    $('.reset-filters').on('click', resetFilters);

    function resetFilters() {
        fetchData("oracle_api.php").done(handleApiResponse).fail(handleError);
    }

    function handleApiResponse(data) {
        const parsedData = JSON.parse(data);
        const jobs = parsedData.jobs;
        const categories = parsedData.categories.sort((a, b) => a.localeCompare(b));
        const locations = parsedData.locations.sort((a, b) => a.localeCompare(b));
        const degreeLevels = parsedData.degreeLevels.sort((a, b) => a.localeCompare(b));

        renderJobElements(jobs);
        renderDropdownOptions("#categories", categories);
        renderDropdownOptions(".locations", locations);
        renderDropdownOptions("#degreeLevels", degreeLevels);

        $(".loader").removeClass("active");
    }

    function handleError(xhr, status, error) {
        console.log(error);
    }

    fetchData("oracle_api.php").done(handleApiResponse).fail(handleError);
    var locationParams = [];
    var categoryParams = [];
    var degreeParams = [];



$("#job-search-form").on("submit", function(e) {
    e.preventDefault();
    const searchTerm = $('#search-keyword').val();
    console.log(searchTerm)
    fetchData("oracle_api.php", { searchTerm: searchTerm })
            .done(handleApiResponse)
            .fail(handleError);
})



$(".jl-search").on("change", function() {
    var selectedValue = $.trim($(this).val());

    // Determine which parameter group to update based on some criteria
    // For example, you might have different classes or attributes for each group
    var group = $(this).data('group'); // Assuming you use data attributes to differentiate groups

    if (group === 'location') {
        updateParams(locationParams, selectedValue);
    } else if (group === 'category') {
        updateParams(categoryParams, selectedValue);
    } else if (group === 'degree') {
        updateParams(degreeParams, selectedValue);
    }

    // Fetch new data based on all selected values
    fetchData("oracle_api.php", {
        locationValues: JSON.stringify(locationParams),
            categoryValues: JSON.stringify(categoryParams),
            degreeValues: JSON.stringify(degreeParams)
        })
        .done(function(data) {
            handleApiResponse(data);
        })
        .fail(handleError);
});

function updateParams(paramsArray, value) {
    var index = paramsArray.indexOf(value);

    if (index > -1) {
        // Value exists in the array, remove it
        paramsArray.splice(index, 1);
    } else {
        // Value does not exist in the array, add it
        paramsArray.push(value);
    }
    
}




});
</script>
<style>
  .job-container {
            cursor: pointer;
        }
</style>
