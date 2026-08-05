# Identity bounded context

Authentication, OTP, roles, and access codes.

- Domain VOs: `Domain/ValueObjects`
- Live HTTP: `App\Http\Controllers\Auth\*`
- Live service: `App\Services\OtpService`

New identity rules should prefer `Src\Identity\*` over expanding controllers indefinitely.
