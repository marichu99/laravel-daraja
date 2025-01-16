<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phone and Amount Form</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container {
            max-width: 500px;
            margin-top: 50px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center">Phone Number and Amount Form</h2>

        <!-- Display Success Message -->
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <!-- Display Errors -->
        @if($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Form for phone number and amount -->
        <form action="{{ url('/submit-phone-amount') }}" method="POST" onsubmit="return validateForm()">
            @csrf  <!-- CSRF Token -->

            <!-- Phone Number Input -->
            <div class="mb-3">
                <label for="phone" class="form-label">Phone Number</label>
                <input type="text" class="form-control" id="phone" name="phone" placeholder="Enter phone number starting with 254" value="{{ old('phone') }}">
                <div class="invalid-feedback" id="phoneError">Phone number must start with 254.</div>
            </div>

            <!-- Amount Input -->
            <div class="mb-3">
                <label for="amount" class="form-label">Amount</label>
                <input type="number" class="form-control" id="amount" name="amount" placeholder="Enter amount" value="{{ old('amount') }}">
                <div class="invalid-feedback" id="amountError">Amount must be a positive number.</div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary w-100">Submit</button>
        </form>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>

    <!-- JavaScript Validation -->
    <script>
        function validateForm() {
            // Get the values from the input fields
            var phone = document.getElementById("phone").value;
            var amount = document.getElementById("amount").value;

            // Reset previous error messages
            document.getElementById("phoneError").style.display = "none";
            document.getElementById("amountError").style.display = "none";

            var isValid = true;

            // Validate phone number
            if (!/^254\d{9}$/.test(phone)) {
                document.getElementById("phoneError").style.display = "block";
                isValid = false;
            }

            // Validate amount
            if (isNaN(amount) || amount <= 0) {
                document.getElementById("amountError").style.display = "block";
                isValid = false;
            }

            // Prevent form submission if invalid
            return isValid;
        }
    </script>
</body>
</html>
