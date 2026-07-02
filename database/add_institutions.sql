USE bookmart;

INSERT INTO universities (name, city, institution_type) VALUES
('Monash South Africa', 'Johannesburg', 'private'),
('MANCOSA', 'Durban', 'private'),
('REGENT Business School', 'Durban', 'private'),
('Milpark Education', 'Johannesburg', 'private'),
('Boston City Campus & Business College', 'Johannesburg', 'private'),
('Varsity College (IIE)', 'Johannesburg', 'private'),
('Rosebank College (IIE)', 'Pretoria', 'private'),
('STADIO Higher Education', 'Pretoria', 'private'),
('Belgium Campus', 'Pretoria', 'private'),
('Pearson Institute of Higher Education', 'Midrand', 'private'),
('IIE MSA (formerly Monash)', 'Johannesburg', 'private'),
('Damelin', 'Johannesburg', 'private'),
('CTU Training Solutions', 'Pretoria', 'private'),
('Academy of York', 'Randburg', 'private'),
('Cornerstone Institute', 'Cape Town', 'private'),
('AFDA', 'Johannesburg', 'private'),
('Inscape Education Group', 'Cape Town', 'private'),
('Oakfields College', 'Pretoria', 'private'),
('Lyceum College', 'Johannesburg', 'private'),
('Centurion Academy', 'Pretoria', 'private');

INSERT INTO campuses (university_id, name, pickup_point)
SELECT u.id, 'Main Campus', 'Student Centre / Reception'
FROM universities u
WHERE u.institution_type = 'private'
AND NOT EXISTS (
    SELECT 1 FROM campuses c WHERE c.university_id = u.id AND c.name = 'Main Campus'
);
