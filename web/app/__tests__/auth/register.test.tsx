import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import RegisterPage from "@/app/auth/register/page";
import { useRouter } from "next/navigation";
import { act } from "react";
import { AuthService } from "@/services/auth/AuthService";

jest.mock("next/navigation", () => ({
  useRouter: jest.fn(),
}));

jest.mock("@/services/auth/AuthService", () => {
  const mockInstance = {
    register: jest.fn(),
    setToken: jest.fn(),
  };

  return {
    AuthService: {
      getInstance: jest.fn(() => mockInstance),
    },
  };
});

describe("RegisterPage", () => {
  const mockRouter = { push: jest.fn() };
  let mockAuthService: ReturnType<typeof AuthService.getInstance>;

  beforeEach(() => {
    (useRouter as jest.Mock).mockReturnValue(mockRouter);
    mockRouter.push.mockClear();

    mockAuthService = AuthService.getInstance();
    (mockAuthService.register as jest.Mock).mockResolvedValue({ 
      data: { token: 'fake-token', user: {} } 
    });
  });

  it("renders registration form", () => {
    render(<RegisterPage />);
    expect(screen.getByText("Create an Account")).toBeInTheDocument();
    expect(screen.getByLabelText("First Name")).toBeInTheDocument();
    expect(screen.getByLabelText("Last Name")).toBeInTheDocument();
    expect(screen.getByLabelText("Email")).toBeInTheDocument();
    expect(screen.getByLabelText("Password")).toBeInTheDocument();
    expect(screen.getByLabelText("Confirm Password")).toBeInTheDocument();
    expect(screen.getByRole("button", { name: "Register" })).toBeInTheDocument();
  });

  it("validates required fields", async () => {
    render(<RegisterPage />);

    await act(async () => {
      fireEvent.submit(screen.getByRole("form"));
    });

    await waitFor(() => {
      expect(screen.getByText("First name is required")).toBeInTheDocument();
      expect(screen.getByText("Last name is required")).toBeInTheDocument();
      expect(screen.getByText("Email is required")).toBeInTheDocument();
      expect(screen.getByText("Password is required")).toBeInTheDocument();
      expect(screen.getByText("Please confirm your password")).toBeInTheDocument();
    });
  });

  it("validates password requirements", async () => {
    render(<RegisterPage />);

    await act(async () => {
      const passwordInput = screen.getByLabelText("Password");
      
      fireEvent.change(passwordInput, { target: { value: "weak" } });
      fireEvent.submit(screen.getByRole("form"));
    });

    await waitFor(() => {
      expect(screen.getByText("Password must be at least 8 characters")).toBeInTheDocument();
    });

    await act(async () => {
      const passwordInput = screen.getByLabelText("Password");
      fireEvent.change(passwordInput, { target: { value: "weakpassword" } });
      fireEvent.submit(screen.getByRole("form"));
    });

    await waitFor(() => {
      expect(screen.getByText("Password must contain at least one uppercase letter")).toBeInTheDocument();
    });
  });

  it("validates password confirmation", async () => {
    render(<RegisterPage />);

    await act(async () => {
      // Fill in all required fields
      fireEvent.change(screen.getByLabelText("First Name"), { target: { value: "John" } });
      fireEvent.change(screen.getByLabelText("Last Name"), { target: { value: "Doe" } });
      fireEvent.change(screen.getByLabelText("Email"), { target: { value: "john@example.com" } });
      fireEvent.change(screen.getByLabelText("Password"), { target: { value: "StrongPass123" } });
      fireEvent.change(screen.getByLabelText("Confirm Password"), { target: { value: "DifferentPass123" } });
      fireEvent.submit(screen.getByRole("form"));
    });

    await waitFor(() => {
      expect(screen.getByText("Passwords do not match")).toBeInTheDocument();
    });
  });

  it("submits form with valid data", async () => {
    render(<RegisterPage />);

    const validData = {
      first_name: "John",
      last_name: "Doe",
      email: "john@example.com",
      password: "StrongPass123",
      password_confirmation: "StrongPass123",
      role: "artist",
    };

    await act(async () => {
      fireEvent.change(screen.getByLabelText("First Name"), { target: { value: validData.first_name } });
      fireEvent.change(screen.getByLabelText("Last Name"), { target: { value: validData.last_name } });
      fireEvent.change(screen.getByLabelText("Email"), { target: { value: validData.email } });
      fireEvent.change(screen.getByLabelText("Password"), { target: { value: validData.password } });
      fireEvent.change(screen.getByLabelText("Confirm Password"), { target: { value: validData.password_confirmation } });
      fireEvent.submit(screen.getByRole("form"));
    });

    await waitFor(() => {
      expect(mockAuthService.register).toHaveBeenCalledWith(validData);
      expect(mockRouter.push).toHaveBeenCalledWith("/auth/login?registered=true");
    });
  });

  it("handles registration error", async () => {
    const errorMessage = "Email already taken";
    (mockAuthService.register as jest.Mock).mockRejectedValue(new Error(errorMessage));

    render(<RegisterPage />);

    await act(async () => {
      fireEvent.change(screen.getByLabelText("First Name"), { target: { value: "John" } });
      fireEvent.change(screen.getByLabelText("Last Name"), { target: { value: "Doe" } });
      fireEvent.change(screen.getByLabelText("Email"), { target: { value: "john@example.com" } });
      fireEvent.change(screen.getByLabelText("Password"), { target: { value: "StrongPass123" } });
      fireEvent.change(screen.getByLabelText("Confirm Password"), { target: { value: "StrongPass123" } });
      fireEvent.submit(screen.getByRole("form"));
    });

    await waitFor(() => {
      expect(screen.getByRole("alert")).toHaveTextContent(errorMessage);
    });
  });
}); 