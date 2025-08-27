<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<body>
<div class="hero-image">
    <div class="hero-text">
        <h1>I am John Doe</h1>
        <p>And I'm a Photographer</p>
    </div>
</div>
</body>

<style>
    body, html {
        height: 188.97637795px;
        width: 264.56692913px;
    }

    /* The hero image */
    .hero-image {

        /* Set a specific height */
        height: 188.97637795px;
        width: 264.56692913px;

        /* Position and center the image to scale nicely on all screens */
        background-position: center;
        background-repeat: no-repeat;
        background-size: cover;
        background-color: white;
        position: relative;
    }

    /* Place text in the middle of the image */
    .hero-text {
        text-align: center;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: black;
    }
</style>
</html>
