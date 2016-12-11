provider "aws" {
  access_key = "AKIAILUQ6KVCBVAIAHXA"
  secret_key = "fOLZGHl/+0cl9gntWmt5Z83LPNdB5lw4+ZJbkeTx"
  region     = "us-east-1"
}

resource "aws_instance" "example" {
  ami           = "ami-0d729a60"
  instance_type = "t2.micro"
provisioner "local-exec" {
    command = "echo ${aws_instance.example.public_ip} > ip_address.txt"
  }

}

resource "aws_eip" "ip" {
    instance = "${aws_instance.example.id}"
    depends_on = ["aws_instance.example"]
}

